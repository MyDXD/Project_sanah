// Render the documented diagrams to a standalone offline HTML document and SVGs.
const {chromium}=require('playwright');
const fs=require('fs');const path=require('path');
const root=path.resolve(__dirname,'..');
const escape=text=>text.replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
(async()=>{
 const markdown=fs.readFileSync(path.join(root,'SYSTEM_FLOW.md'),'utf8');
 const diagrams=[...markdown.matchAll(/```mermaid\r?\n([\s\S]*?)```/g)].map((match,index)=>({source:match[1],index,title:[...markdown.slice(0,match.index).matchAll(/^#{2,3} (.+)$/gm)].at(-1)[1]}));
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try{
  const page=await browser.newPage();await page.setContent('<html><head><meta charset="utf-8"></head><body></body></html>');
  await page.addScriptTag({url:'https://cdn.jsdelivr.net/npm/mermaid@11.12.0/dist/mermaid.min.js'});
  await page.evaluate(()=>mermaid.initialize({startOnLoad:false,theme:'base',securityLevel:'strict',fontFamily:'Tahoma, Arial, sans-serif',themeVariables:{primaryColor:'#fff7e9',primaryTextColor:'#5f4a35',primaryBorderColor:'#9a641f',lineColor:'#9a641f',secondaryColor:'#e8f2ed',tertiaryColor:'#f3e9d8'},flowchart:{htmlLabels:false,useMaxWidth:false},er:{useMaxWidth:false}}));
  const directory=path.join(root,'docs','flow');fs.mkdirSync(directory,{recursive:true});
  let sections=[];
  for(const diagram of diagrams){
   const svg=await page.evaluate(async({source,index})=>(await mermaid.render('flow'+index,source)).svg,diagram);
   fs.writeFileSync(path.join(directory,`flow-${String(diagram.index+1).padStart(2,'0')}.svg`),svg);
   sections.push(`<section id="flow-${diagram.index}"><h2>${escape(diagram.title)}</h2><div class="diagram">${svg}</div><div class="controls"><button onclick="zoom(this,1.2)">ขยาย +</button><button onclick="zoom(this,1/1.2)">ย่อ −</button><button onclick="resetZoom(this)">พอดีหน้าจอ</button><a href="flow-${String(diagram.index+1).padStart(2,'0')}.svg" download>ดาวน์โหลด SVG</a></div></section>`);
   console.log('Rendered',diagram.title);
  }
  const navigation=diagrams.map(diagram=>`<a href="#flow-${diagram.index}">${escape(diagram.title)}</a>`).join('');
  const html=`<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Flow ระบบร้านสานะกิมหยง</title><style>*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:Tahoma,Arial,sans-serif;color:#5f4a35;background:#faf6ef}aside{position:fixed;inset:0 auto 0 0;width:260px;overflow:auto;padding:24px 18px;background:#1f2937;color:white}aside h2{font-size:19px}aside a{display:block;color:#e5e7eb;text-decoration:none;padding:10px 5px;font-size:14px;line-height:1.6}main{margin-left:260px;padding:30px;max-width:1600px}h1{font-size:28px}h2{font-size:21px}.intro,section{border:1px solid #eadfce;background:white;border-radius:18px;padding:24px;margin-bottom:26px}.intro p{line-height:1.9}.diagram{overflow:auto;max-height:900px;padding:20px;background:#fffdf9;border-radius:12px;text-align:center}.diagram svg{display:block;max-width:100%;height:auto;margin:auto}.controls{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}button,.controls a{border:0;border-radius:10px;padding:10px 15px;background:#9a641f;color:white;text-decoration:none;cursor:pointer;font:inherit}.controls a{background:#eadfce;color:#5f4a35}.note{background:#fff1ca;padding:18px;border-radius:12px;line-height:1.9}@media(max-width:800px){aside{position:static;width:100%;max-height:220px}main{margin:0;padding:15px}section{padding:15px}h1{font-size:24px}}@media print{aside,.controls,.print-button{display:none}main{margin:0;padding:0;max-width:none}section{break-before:page;border:0;box-shadow:none}.diagram{max-height:none;overflow:visible;padding:0}.diagram svg{max-width:100%!important;width:auto!important;max-height:250mm}.intro{border:0}h2{font-size:18px}}</style></head><body><aside><h2>สานะกิมหยง 🤎</h2>${navigation}</aside><main><h1>Flow ระบบร้านสานะกิมหยง</h1><div class="intro"><p>แผนภาพ ${diagrams.length} ชุด อ้างอิงพฤติกรรมจากโค้ดปัจจุบันของโปรเจ็กต์ แยกงาน Admin, Employee และ User พร้อมกระบวนการตรวจสิทธิ์ การขาย Stock และรายงาน</p><p class="note">ใส่ตะกร้ายังไม่ตัด Stock • ตัด Stock เมื่อยืนยันออเดอร์หรือขาย POS • แนบสลิปยังไม่ยืนยันรับเงิน • ยกเลิกออเดอร์คืน Stock ครั้งเดียว • ยอดขายรายงานนับรายการสำเร็จ • ทุนย้อนหลังที่ไม่มีข้อมูลจะแสดงว่าไม่ทราบ</p><p>อ่านคำอธิบายและตารางประกอบใน <a href="../../SYSTEM_FLOW.md">SYSTEM_FLOW.md</a></p><button class="print-button" onclick="window.print()">พิมพ์ / บันทึกเป็น PDF</button></div>${sections.join('')}</main><script>function zoom(button,factor){const svg=button.closest('section').querySelector('svg');const width=svg.getBoundingClientRect().width;svg.style.maxWidth='none';svg.style.width=(width*factor)+'px';svg.style.height='auto'}function resetZoom(button){const svg=button.closest('section').querySelector('svg');svg.style.width='';svg.style.maxWidth='100%';svg.style.height='auto'}</script></body></html>`;
  fs.writeFileSync(path.join(directory,'index.html'),html);
  await page.goto('file:///'+path.join(directory,'index.html').replace(/\\/g,'/'));
  await page.setViewportSize({width:1440,height:1000});await page.screenshot({path:path.join(directory,'preview.png')});
  console.log('Saved docs/flow/index.html; diagrams:',diagrams.length);
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
