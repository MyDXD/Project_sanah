"""End-to-end checks using temporary fixtures; always cleans its own data.
Run: python tests/integration.py (Docker must be running).
"""
import base64
import http.cookiejar
import json
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
from concurrent.futures import ThreadPoolExecutor

BASE = 'http://127.0.0.1:8000'
checks = 0

def cli(mode):
    result = subprocess.run(['docker','compose','exec','-T','web','php','tests/fixtures.php',mode],capture_output=True,text=True,encoding='utf-8')
    if result.returncode:
        raise RuntimeError(result.stderr + result.stdout)
    return json.loads(result.stdout) if mode in ['setup','state'] else result.stdout

def check(condition, label):
    global checks
    assert condition, label
    checks += 1
    print('PASS', label)

class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(self, path, data=None, multipart=None):
        headers = {}
        if multipart:
            fields, name, content = multipart
            boundary = 'sanah-integration-boundary'
            parts = []
            for key, value in fields.items():
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="payment_slip"; filename="{name}"\r\nContent-Type: image/png\r\n\r\n'.encode()+content+b'\r\n')
            parts.append(f'--{boundary}--\r\n'.encode())
            payload = b''.join(parts)
            headers['Content-Type'] = f'multipart/form-data; boundary={boundary}'
        else:
            payload = None if data is None else urllib.parse.urlencode(data).encode()
        request = urllib.request.Request(BASE+path,data=payload,headers=headers)
        try:
            response = self.opener.open(request,timeout=30)
        except urllib.error.HTTPError as error:
            response = error
        raw = response.read()
        text = raw.decode('utf-8') if not response.headers.get('Content-Type','').startswith('image/') else ''
        assert 'Fatal error' not in text and 'Warning:' not in text, path + ' PHP error: ' + text[:2200]
        return response.status, text, response.url

    def get(self,path):
        return self.request(path)

    def post(self,path,data):
        status,text,_ = self.get(path)
        assert status == 200, (path,status)
        token = re.search(r'name="csrf" value="([a-f0-9]+)"',text)
        if not token:
            token = re.search(r'name="csrf" value="([a-f0-9]+)"',self.get('/profile.php')[1])
        assert token, 'No CSRF on '+path
        return self.request(path,{**data,'csrf':token.group(1)})

    def login(self,email,password='Integration123!'):
        return self.post('/login.php',{'email':email,'password':password})

def run(f):
    pid=f['product']; tag=f['tag']; users=f['users']
    clients={label:Client() for label in users}
    for label in ['admin','employee','user','outsider']:
        status,text,url=clients[label].login(users[label]['email'])
        check(status==200 and '/login.php' not in url, 'login '+label)
    admin,employee,user,outsider=[clients[label] for label in ['admin','employee','user','outsider']]
    check('/login.php' in clients['disabled'].login(users['disabled']['email'])[2],'suspended login blocked')
    for path in ['/admin/dashboard.php','/admin/products.php','/admin/users.php','/admin/reports.php','/admin/settings.php','/admin/qr.php','/admin/feedback.php','/admin/create_product.php','/admin/delete_customer.php']:
        check(employee.get(path)[0]==403,'employee denied '+path)
        check(user.get(path)[0]==403,'user denied '+path)
    check(user.get('/employee/pos.php')[0]==403,'user denied employee POS')
    check(employee.get('/report_user.php')[0]==403,'employee denied customer feedback')
    for path in ['/admin/dashboard.php','/admin/products.php','/admin/products.php?new=1','/admin/users.php','/admin/users.php?new=1','/admin/feedback.php','/admin/settings.php','/admin/qr.php','/admin/stock_history.php','/admin/reports.php','/admin/orders.php','/admin/sales.php']:
        check(admin.get(path)[0]==200,'admin page '+path)
    for path in ['/employee/dashboard.php','/employee/pos.php','/employee/stock.php','/employee/stock_history.php','/employee/orders.php','/employee/sales.php']:
        status,text,_=employee.get(path)
        check(status==200 and 'unit_cost' not in text and 'cost_price' not in text and '\u0e01\u0e33\u0e44\u0e23' not in text and '\u0e23\u0e32\u0e04\u0e32\u0e17\u0e38\u0e19' not in text,'employee cost hidden '+path)
    check(Client().get('/DB/sanahstore.sql')[0]==403,'SQL download blocked')
    check(Client().get('/app/legacy/cart.php')[0]==403,'legacy endpoint blocked')
    check(Client().get('/tests/.fixtures.json')[0]==403,'fixture access blocked')
    state=cli('state')
    admin.request('/admin/stock.php',{'action':'receive','product_id':pid,'quantity':5,'reason':'Invalid CSRF'})
    check(cli('state')['product']['stock']==state['product']['stock'],'CSRF blocks stock mutation')
    employee.post('/employee/stock.php',{'action':'receive','product_id':pid,'quantity':5,'reason':'Integration receive','cost_price':1})
    check(cli('state')['product']['stock']==55 and cli('state')['product']['cost_price']=='10.00','employee receipt audited and cost unchanged')
    employee.post('/employee/pos.php',{'action':'sale',f'quantity[{pid}]':-1,'payment_method':'cash'})
    check(not cli('state')['pos'],'negative quantity rejected')
    employee.post('/employee/pos.php',{'action':'sale',f'quantity[{pid}]':999,'payment_method':'cash'})
    check(not cli('state')['pos'] and cli('state')['product']['stock']==55,'insufficient stock rolls back POS')
    for method in ['cash','transfer','qr']:
        status,text,url=employee.post('/employee/pos.php',{'action':'sale',f'quantity[{pid}]':2,'payment_method':method,'price':1,'cost_price':0})
        check(status==200 and 'id=' in url,'POS '+method)
    state=cli('state')
    check(state['product']['stock']==49,'POS stock deducted')
    check(all(s['total']=='50.00' for s in state['pos']),'POS uses server price')
    check(all(i['unit_cost']=='10.00' for i in state['pos_items']),'POS snapshots cost')
    sale=state['pos'][0]['id']
    employee.post('/employee/pos.php',{'action':'sale',f'quantity[{pid}]':1,'payment_method':'cash','edit_id':sale})
    check(cli('state')['product']['stock']==49,'employee cannot edit sale')
    admin.post('/admin/pos.php?edit='+str(sale),{'action':'sale',f'quantity[{pid}]':3,'payment_method':'cash','edit_id':sale,'note':'Edited'})
    check(cli('state')['product']['stock']==48,'admin sale edit restores then deducts stock')
    for page in ['/product_content.php?id='+str(pid),'/profile.php','/report_user.php','/order.php','/scan.php']:
        check(user.get(page)[0]==200,'customer page '+page)
    for _ in range(2):
        user.post('/product_content.php?id='+str(pid),{'action':'add_cart','product_id':pid,'quantity':2})
        user.post('/cart.php',{'action':'checkout',f'quantity[{pid}]':2,'delivery_address':'Test address','phone_number':'0123456789','payment_method':'transfer','price':1})
    state=cli('state'); orders=state['orders']
    check(len(orders)==2 and state['product']['stock']==44,'online checkout and stock')
    check(all(o['total_price']=='50.00' for o in orders),'online server price')
    check(all(i['unit_cost']=='10.00' for i in state['order_items']),'online snapshots cost')
    oid=orders[0]['order_id']; cancel=orders[1]['order_id']
    check(outsider.get('/order_detail.php?order_id='+str(oid))[0]==404,'customer cannot see another order')
    outsider.post('/order.php',{'action':'status','order_id':oid,'status':'Cancelled'})
    check(cli('state')['orders'][0]['order_status']=='Order Placed','customer cannot cancel another order')
    user.post('/order.php',{'action':'status','order_id':cancel,'status':'Cancelled'})
    check(cli('state')['product']['stock']==46,'cancellation restores stock')
    user.post('/order.php',{'action':'status','order_id':cancel,'status':'Cancelled'})
    check(cli('state')['product']['stock']==46,'repeated cancellation does not restore twice')
    employee.post('/employee/orders.php?id='+str(oid),{'action':'status','order_id':oid,'status':'Shipped'})
    check(cli('state')['orders'][0]['order_status']=='Order Placed','invalid status jump blocked')
    employee.post('/employee/orders.php?id='+str(oid),{'action':'status','order_id':oid,'status':'Order Processing'})
    employee.post('/employee/orders.php?id='+str(oid),{'action':'status','order_id':oid,'status':'Shipped'})
    check(cli('state')['orders'][0]['order_status']=='Order Processing','shipping before verified payment blocked')
    _,text,_=user.get('/order_detail.php?order_id='+str(oid));token=re.search(r'name="csrf" value="([a-f0-9]+)"',text).group(1)
    png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=')
    user.request('/order_detail.php?order_id='+str(oid),multipart=({'csrf':token,'action':'slip','order_id':oid},'proof.png',png))
    state=cli('state'); slip=state['orders'][0]['payment_slip']
    check(bool(slip) and state['orders'][0]['payment_status']=='unverified','slip uploaded without auto-verifying payment')
    check(user.get('/slip.php?id='+str(oid))[0]==200,'owner can read payment slip')
    check(employee.get('/slip.php?id='+str(oid))[0]==200,'employee can verify payment slip')
    check(outsider.get('/slip.php?id='+str(oid))[0]==404,'other customer cannot read payment slip')
    check(Client().get('/'+slip)[0]==403,'direct payment slip access blocked')
    employee.post('/employee/orders.php?id='+str(oid),{'action':'payment','order_id':oid})
    employee.post('/employee/orders.php?id='+str(oid),{'action':'tracking','order_id':oid,'transport_id':'TEST123'})
    employee.post('/employee/orders.php?id='+str(oid),{'action':'status','order_id':oid,'status':'Shipped'})
    user.post('/order.php',{'action':'status','order_id':oid,'status':'Completed'})
    check(cli('state')['orders'][0]['order_status']=='Completed','payment prepare ship customer confirm flow')
    admin.post('/admin/products.php?edit='+str(pid),{'action':'product','product_id':pid,'product_name':tag,'product_type':'Test','product_detail':'Edited','product_price':'25.00','cost_price':'99.00','stock':46,'low_stock_threshold':5})
    check(cli('state')['product']['cost_price']=='99.00','admin edits product cost')
    check(all(i['unit_cost']=='10.00' for i in cli('state')['order_items']),'later cost changes preserve history')
    user.post('/profile.php',{'action':'profile','first_name':'Updated','last_name':'Test','phone_number':'0123456789','address':'Address','role':'admin'})
    check(user.get('/admin/dashboard.php')[0]==403,'profile cannot escalate role')
    user.post('/profile.php',{'action':'password','current_password':'Integration123!','new_password':'Changed123!','confirm_password':'Changed123!'})
    fresh=Client();check('/login.php' not in fresh.login(users['user']['email'],'Changed123!')[2],'password change')
    user.post('/report_user.php',{'action':'feedback','issue_title':tag,'issue_description':'Feedback test'})
    _,text,_=admin.get('/admin/feedback.php'); rid=re.search(r'name="report_id" value="(\d+)"',text).group(1)
    admin.post('/admin/feedback.php',{'action':'reply','report_id':rid,'admin_response':'Reply test','status':'resolved'})
    check('Reply test' in user.get('/report_user.php')[1],'customer sees own feedback reply')
    check('Reply test' not in outsider.get('/report_user.php')[1],'feedback ownership enforced')
    new=Client();_,text,_=new.get('/register.php');token=re.search(r'name="csrf_token" value="([a-f0-9]+)"',text).group(1)
    new.request('/register.php',{'csrf_token':token,'email':tag+'_registered@example.com','first_name':'Register','last_name':'Test','phone_number':'0123456789','address':'Test','password':'Register123!','passwordcheck':'Register123!','role':'admin'})
    check(cli('state')['registered']['role']=='user','registration ignores injected admin role')
    check(new.get('/admin/dashboard.php')[0]==403,'registered customer denied admin')
    created_email=tag+'_created@example.com'
    admin.post('/admin/users.php?new=1',{'action':'user','user_id':0,'email':created_email,'first_name':'NewStaff','last_name':'Test','phone_number':'0123456789','address':'Test','role':'employee','password':'Integration123!','is_active':1})
    check(cli('state')['created']['role']=='employee','admin creates employee account')
    created_client=Client();check('/employee/dashboard.php' in created_client.login(created_email)[2],'created employee gets employee dashboard')
    # Competing sales use different sessions and connections. Only one can win.
    admin.post('/admin/products.php?edit='+str(pid),{'action':'product','product_id':pid,'product_name':tag,'product_type':'Test','product_detail':'Race','product_price':'25.00','cost_price':'10.00','stock':3,'low_stock_threshold':5})
    count=len(cli('state')['pos'])
    with ThreadPoolExecutor(max_workers=2) as pool:
        futures=[pool.submit(client.post,path,{'action':'sale',f'quantity[{pid}]':2,'payment_method':'cash'}) for client,path in [(admin,'/admin/pos.php'),(employee,'/employee/pos.php')]]
        for future in futures: future.result()
    check(len(cli('state')['pos'])==count+1 and cli('state')['product']['stock']==1,'concurrent sales cannot oversell')
    admin.post('/admin/products.php',{'action':'archive','product_id':pid})
    check(user.get('/product_content.php?id='+str(pid))[0]==404,'archived product hidden from customers')
    check(len(cli('state')['order_items'])==2,'archive preserves historical orders')
    admin.post('/admin/products.php?edit='+str(pid),{'action':'product','product_id':pid,'product_name':tag,'product_type':'Test','product_detail':'Restored','product_price':'25.00','cost_price':'10.00','stock':1,'low_stock_threshold':5})
    check(user.get('/product_content.php?id='+str(pid))[0]==200,'admin can reactivate archived product')
    state=cli('state');check(all(m['actor_id'] for m in state['stock']),'stock changes have actors')
    check(sum(m['delta'] for m in state['stock'])==state['product']['stock']-50,'stock ledger reconciles')
    admin.post('/admin/users.php?edit='+str(users['employee']['id']),{'action':'user','user_id':users['employee']['id'],'email':users['employee']['email'],'first_name':'Staff','last_name':'Test','phone_number':'0123456789','address':'Test','role':'employee','password':''})
    check('/login.php' in employee.get('/employee/dashboard.php')[2],'admin suspension revokes existing session')

if __name__=='__main__':
    fixtures=cli('setup')
    try:
        run(fixtures)
        print(f'{checks} integration checks passed')
    finally:
        print(cli('cleanup').strip())
