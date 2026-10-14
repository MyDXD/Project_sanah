const {chromium} = require('playwright');
const {execFileSync} = require('child_process');
const fs = require('fs');
const path = require('path');
function cli(mode){const result=execFileSync('docker',['compose','exec','-T','web','php','tests/fixtures.php',mode],{cwd:path.resolve(__dirname,'..'),encoding:'utf8'});return mode==='setup'?JSON.parse(result):result;}
function assert(condition,message){if(!condition)throw new Error(message);console.log('PASS '+message);}
(async()=>{
 const fixtures=cli('setup');let browser;
 const artifacts=path.join(__dirname,'artifacts');fs.mkdirSync(artifacts,{recursive:true});
 try{
  browser=await chromium.launch({channel:'chrome',headless:true});
  const errors=[];
  const context=await browser.newContext({viewport:{width:1440,height:1000}});
  const page=await context.newPage();page.on('pageerror',error=>errors.push(error.message));
  async function login(label){await page.goto('http://localhost:8000/logout.php');await page.locator('input[name=email]').fill(fixtures.users[label].email);await page.locator('input[name=password]').fill('Integration123!');await Promise.all([page.waitForURL(label==='user'?'**/index.php':`**/${label}/dashboard.php`),page.locator('button[name=login]').click()]);}
  await login('admin');
  await page.screenshot({path:path.join(artifacts,'admin-dashboard.png'),fullPage:true});
  await page.goto(`http://localhost:8000/admin/qr.php?id=${fixtures.product}`);
  await page.waitForSelector('[data-qr] svg');
  const qrImage=await page.locator('[data-qr] svg').screenshot();
  await page.addScriptTag({url:'http://localhost:8000/assets/vendor/jsQR.js'});
  const decoded=await page.evaluate(async()=>{const svg=document.querySelector('[data-qr] svg').outerHTML;const img=new Image();const url=URL.createObjectURL(new Blob([svg],{type:'image/svg+xml'}));await new Promise((resolve,reject)=>{img.onload=resolve;img.onerror=reject;img.src=url;});const canvas=document.createElement('canvas');canvas.width=400;canvas.height=400;const ctx=canvas.getContext('2d');ctx.fillStyle='white';ctx.fillRect(0,0,400,400);ctx.drawImage(img,0,0,400,400);const frame=ctx.getImageData(0,0,400,400);URL.revokeObjectURL(url);return jsQR(frame.data,400,400)?.data;});
  assert(decoded===`http://localhost:8000/product_content.php?id=${fixtures.product}`,'QR decodes to product ID URL');
  await page.screenshot({path:path.join(artifacts,'product-qr.png'),fullPage:true});
  await login('employee');
  await page.goto('http://localhost:8000/employee/pos.php');
  await page.waitForFunction(()=>typeof attachScanner==='function'&&typeof productIdFromQR==='function');
  await page.evaluate(async data=>{const canvas=document.createElement('canvas');canvas.width=500;canvas.height=500;const ctx=canvas.getContext('2d');ctx.fillStyle='white';ctx.fillRect(0,0,500,500);const img=new Image();await new Promise(resolve=>{img.onload=resolve;img.src='data:image/png;base64,'+data;});ctx.drawImage(img,50,50,400,400);window.testCamera=canvas.captureStream(10);navigator.mediaDevices.getUserMedia=async()=>window.testCamera;},qrImage.toString('base64'));
  await page.locator('#start-scan').click();
  await page.waitForFunction(id=>document.querySelector(`[data-product="${id}"] input`).value==='1',fixtures.product,{timeout:15000});
  assert(await page.locator('#pos-total').textContent()==='25.00','camera scan adds product and calculates price');
  await page.locator('#pos-search').fill(decoded);await page.locator('#pos-search').press('Enter');
  assert(await page.locator(`[data-product="${fixtures.product}"] input`).inputValue()==='2','hardware QR scanner input adds product');
  await page.screenshot({path:path.join(artifacts,'employee-pos.png'),fullPage:true});
  await page.setViewportSize({width:390,height:844});await page.goto('http://localhost:8000/employee/dashboard.php');
  assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'employee mobile layout fits viewport');
  await page.screenshot({path:path.join(artifacts,'employee-mobile.png'),fullPage:true});
  await page.goto(`http://localhost:8000/product_content.php?id=${fixtures.product}`);
  assert(!await page.locator('body').textContent().then(text=>text.includes('ราคาทุน')||text.includes('กำไร')),'public product has no cost/profit');
  await page.screenshot({path:path.join(artifacts,'customer-product-mobile.png'),fullPage:true});
  await page.goto('http://localhost:8000/register.php');await page.waitForFunction(()=>Number(getComputedStyle(document.querySelector('.signup-card')).opacity)===1);await page.screenshot({path:path.join(artifacts,'register-mobile.png'),fullPage:true});
  assert(errors.length===0,'no browser JavaScript errors: '+errors.join(';'));
  console.log('Browser checks passed; screenshots in tests/artifacts');
 } finally {if(browser)await browser.close();console.log(cli('cleanup').trim());}
})().catch(error=>{console.error(error);process.exitCode=1;});
