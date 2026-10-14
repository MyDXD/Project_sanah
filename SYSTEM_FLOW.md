# Flow ระบบร้านสานะกิมหยง

เอกสารนี้อธิบายพฤติกรรมจากโค้ดปัจจุบันของโปรเจ็กต์ ครอบคลุม Admin, Employee, User และผู้เยี่ยมชมที่ยังไม่ล็อกอิน

## 1. ภาพรวมระบบ

```mermaid
flowchart TD
    Start(["เข้าระบบร้านสานะกิมหยง"]) --> Public["หน้าแรก / สินค้า / ค้นหา / กรองประเภท / สแกน QR"]
    Public --> Login["เข้าสู่ระบบ"]
    Public --> Register["สมัครสมาชิก"]
    Register --> Validate["ตรวจข้อมูล / อีเมลซ้ำ / ยืนยันรหัสผ่าน"]
    Validate --> NewUser["บันทึกบัญชี User / Hash รหัสผ่าน / เริ่ม Session"]
    NewUser --> Customer
    Login --> Auth{"บัญชีใช้งานได้และรหัสผ่านถูกต้อง?"}
    Auth -->|ไม่| Reject["แสดงข้อผิดพลาด"]
    Reject --> Login
    Auth -->|ใช่| Role{"Role ในฐานข้อมูล"}
    Role -->|Admin| Admin["Dashboard ผู้ดูแล"]
    Role -->|Employee| Staff["Dashboard พนักงาน"]
    Role -->|User| Customer["เว็บไซต์ลูกค้า"]
    Admin --> AdminJobs["สินค้า / QR / ผู้ใช้ / ออเดอร์ / POS / Stock / ความคิดเห็น / รายงาน / ตั้งค่า"]
    Staff --> StaffJobs["POS / สแกน QR / รับสินค้า / ประวัติ Stock / ออเดอร์ออนไลน์ / โปรไฟล์"]
    Customer --> UserJobs["ตะกร้า / สั่งซื้อ / แนบสลิป / ติดตามออเดอร์ / ความคิดเห็น / โปรไฟล์"]
    AdminJobs --> Shared[("สินค้า + Stock + ออเดอร์ + การขาย + ผู้ใช้งาน")]
    StaffJobs --> Shared
    UserJobs --> Shared
```

ผู้เยี่ยมชมดูสินค้า ราคา จำนวนพร้อมขาย และสแกน QR ได้ การเพิ่มสินค้าในตะกร้าหรือทำรายการส่วนตัวต้องล็อกอินก่อน การสมัครสมาชิกผ่านหน้าเว็บไซต์สร้าง Role `user` เสมอ

## 2. การล็อกอินและตรวจสิทธิ์

```mermaid
flowchart TD
    A["กรอกอีเมลและรหัสผ่าน"] --> B{"CSRF ถูกต้อง?"}
    B -->|ไม่| Error["ปฏิเสธและแสดงข้อผิดพลาด"]
    B -->|ใช่| C["ค้นหาบัญชีด้วยอีเมล"]
    C --> D{"พบบัญชีและ is_active = 1?"}
    D -->|ไม่| Error
    D -->|ใช่| E{"password_verify ผ่าน?"}
    E -->|ไม่| Error
    E -->|ใช่| F["เปลี่ยน Session ID / เก็บ user_id และ Role"]
    F --> G{"Role"}
    G -->|Admin| H["/admin/dashboard.php"]
    G -->|Employee| I["/employee/dashboard.php"]
    G -->|User| J["/index.php"]
    H --> Next["เรียกหน้าใช้งานถัดไป"]
    I --> Next
    J --> Next
    Next --> DBCheck["อ่าน Role และสถานะบัญชีจากฐานข้อมูลอีกครั้ง"]
    DBCheck --> Active{"บัญชียังเปิดใช้งาน?"}
    Active -->|ไม่| Clear["ล้างข้อมูลล็อกอิน / ไปหน้า Login"]
    Active -->|ใช่| Access{"มีสิทธิ์ใช้หน้านี้?"}
    Access -->|ไม่| Forbidden["403 ไม่มีสิทธิ์"]
    Access -->|ใช่| Ownership{"เป็นข้อมูลส่วนตัวของลูกค้า?"}
    Ownership -->|ไม่| Allow["ดำเนินการได้"]
    Ownership -->|ใช่| Owner{"user_id ตรงกับเจ้าของข้อมูล?"}
    Owner -->|ไม่| NotFound["ไม่ให้เข้าถึงข้อมูล"]
    Owner -->|ใช่| Allow
```

ข้อกำหนด:

- หน้าภายใต้ `/admin/` ตรวจว่าเป็น Admin แม้ผู้เรียกจะเป็น Employee ที่มีสิทธิ์ใช้ฟังก์ชันใกล้เคียงกัน
- Employee ใช้หน้าภายใต้ `/employee/` และไม่ได้รับราคาทุนหรือกำไรใน HTML
- การระงับบัญชีมีผลกับ Session เดิมเมื่อเรียกหน้าที่ตรวจบัญชีครั้งถัดไป
- การแก้ข้อมูลผ่านฟอร์มตรวจ CSRF ฝั่งเซิร์ฟเวอร์
- ออเดอร์และความคิดเห็นของลูกค้ากรองด้วย `user_id` ของผู้ล็อกอิน
- สลิปเปิดผ่าน `slip.php` โดยเจ้าของออเดอร์ หรือ Admin/Employee ที่ตรวจการชำระเงิน ไฟล์ในโฟลเดอร์สลิปถูกปิดการเข้าถึงโดยตรง

## 3. สมัครสมาชิกและโปรไฟล์

```mermaid
flowchart TD
    R["เปิด register.php"] --> Input["ชื่อ / นามสกุล / อีเมล / เบอร์ / ที่อยู่ / รหัสผ่าน / ยืนยันรหัส"]
    Input --> Check{"ข้อมูลครบและรูปแบบถูกต้อง?"}
    Check -->|ไม่| Fix["แสดงข้อผิดพลาด / เก็บข้อมูลทั่วไปไว้"]
    Fix --> Input
    Check -->|ใช่| Duplicate{"อีเมลถูกใช้แล้ว?"}
    Duplicate -->|ใช่| Fix
    Duplicate -->|ไม่| Match{"รหัสผ่านทั้งสองช่องตรงกัน?"}
    Match -->|ไม่| Fix
    Match -->|ใช่| Hash["Hash รหัสผ่าน"]
    Hash --> Save["บันทึก userdata / Role = user"]
    Save --> Session["เริ่ม Session และล็อกอินอัตโนมัติ"]
    Session --> Home["หน้าแรก"]
    Home --> Profile["เปิด profile.php"]
    Profile --> Choice{"เลือกดำเนินการ"}
    Choice -->|ข้อมูลส่วนตัว| Details["แก้ชื่อ / นามสกุล / เบอร์ / ที่อยู่"]
    Details --> Own["บันทึกเฉพาะบัญชีของตัวเอง"]
    Choice -->|รหัสผ่าน| Old["กรอกรหัสเดิม + รหัสใหม่ + ยืนยัน"]
    Old --> Valid{"รหัสเดิมถูกต้องและรหัสใหม่ตรงกัน?"}
    Valid -->|ไม่| Retry["แสดงข้อผิดพลาด"]
    Valid -->|ใช่| NewHash["Hash รหัสใหม่ / บันทึก / เปลี่ยน Session ID"]
```

Admin เป็นผู้กำหนด Role ของบัญชีผ่านหน้าจัดการผู้ใช้งาน การเปลี่ยนโปรไฟล์และการสมัครสมาชิกไม่ให้ผู้ใช้เปลี่ยน Role เอง

## 4. ลูกค้าเลือกสินค้าและจัดการตะกร้า

```mermaid
flowchart TD
    A["ดูสินค้าทั้งหมด"] --> B["ค้นหาชื่อ / กรองประเภท"]
    A --> QR["สแกน QR สินค้า"]
    B --> Detail["ดูรายละเอียด / ราคา / จำนวนพร้อมขาย"]
    QR --> Detail
    Detail --> Login{"ล็อกอินแล้ว?"}
    Login -->|ไม่| Signin["ไปหน้า Login"]
    Signin --> Detail
    Login -->|ใช่| Qty["เลือกจำนวน"]
    Qty --> Available{"สินค้าเปิดขายและจำนวนไม่เกิน Stock?"}
    Available -->|ไม่| Error["แจ้งสินค้าหมดหรือจำนวนเกิน Stock"]
    Available -->|ใช่| Cart["เพิ่มหรือรวมจำนวนในตะกร้าของตัวเอง"]
    Cart --> Action{"จัดการตะกร้า"}
    Action -->|เพิ่มหรือลดจำนวน| Update["ตรวจจำนวนและ Stock / บันทึกจำนวน"]
    Action -->|ลบสินค้า| Remove["ลบเฉพาะรายการของตัวเอง"]
    Action -->|สั่งซื้อ| Checkout["กรอกที่อยู่ / เบอร์ / วิธีชำระเงิน"]
    Update --> Cart
    Remove --> Cart
    Checkout --> Confirm["ยืนยันคำสั่งซื้อ"]
```

**การใส่ตะกร้ายังไม่ตัด Stock** ระบบตรวจและตัด Stock อีกครั้งเมื่อยืนยันคำสั่งซื้อ ราคาที่หน้าเว็บใช้แสดงผลจะถูกตรวจจากฐานข้อมูลอีกครั้งตอนบันทึกจริง

## 5. สร้างคำสั่งซื้อออนไลน์และชำระเงิน

```mermaid
flowchart TD
    A["ยืนยันคำสั่งซื้อ"] --> Validate{"ที่อยู่ / เบอร์ / วิธีชำระถูกต้อง?"}
    Validate -->|ไม่| Error["แจ้งข้อผิดพลาดและกลับไปแก้ข้อมูล"]
    Validate -->|ใช่| Begin["เริ่ม Transaction"]
    Begin --> Lock["ล็อกบัญชีและตะกร้า / ตรวจตะกร้าปัจจุบัน"]
    Lock --> CartOK{"รายการตรงกับตะกร้าปัจจุบัน?"}
    CartOK -->|ไม่| Rollback["Rollback / ไม่บันทึกการขายหรือการตัด Stock"]
    CartOK -->|ใช่| Product["ล็อกสินค้า / อ่านราคาขายและราคาทุนจากฐานข้อมูล"]
    Product --> StockOK{"สินค้าเปิดขายและ Stock เพียงพอ?"}
    StockOK -->|ไม่| Rollback
    StockOK -->|ใช่| Deduct["ตัด Stock / บันทึก stock_movements"]
    Deduct --> Save["สร้าง orders และ order_items / เก็บราคาและทุน ณ เวลาสั่งซื้อ"]
    Save --> Clear["ล้างตะกร้า"]
    Clear --> Commit["Commit"]
    Commit --> Pending["สถานะรอดำเนินการ / payment_status = unverified"]
    Pending --> Method{"วิธีชำระเงิน"}
    Method -->|โอน| Transfer["แสดงข้อมูลบัญชีธนาคารของร้าน"]
    Method -->|QR Payment| PayQR["แสดง QR รับเงินที่ร้านตั้งค่าไว้"]
    Transfer --> Pay["ลูกค้าชำระเงิน"]
    PayQR --> Pay
    Pay --> Upload["แนบภาพสลิป JPG / PNG / WebP ไม่เกิน 5 MB"]
    Upload --> FileOK{"เจ้าของออเดอร์ / สถานะ / ไฟล์ถูกต้อง?"}
    FileOK -->|ไม่| UploadError["แจ้งข้อผิดพลาด"]
    FileOK -->|ใช่| Slip["บันทึกหลักฐาน / ยังไม่ยืนยันว่าจ่ายแล้ว"]
    Slip --> Verify["Admin หรือ Employee ตรวจสอบยอดเงิน"]
    Verify --> Paid["ยืนยันรับเงิน / payment_status = paid"]
    Paid --> Fulfillment["ดำเนินการจัดเตรียมและจัดส่ง"]
    Rollback --> Error
```

การแนบสลิปไม่เปลี่ยนสถานะเป็นชำระแล้วอัตโนมัติ เจ้าหน้าที่ต้องยืนยันเอง ระบบปัจจุบันให้เจ้าหน้าที่เริ่มจัดเตรียมก่อนตรวจการชำระเงินได้ แต่ห้ามเปลี่ยนเป็นจัดส่งแล้วหรือสำเร็จหากยังไม่ยืนยันรับเงิน

## 6. สถานะออเดอร์ออนไลน์

```mermaid
stateDiagram-v2
    [*] --> Pending
    state "รอดำเนินการ / Order Placed" as Pending
    state "กำลังจัดเตรียม / Order Processing" as Preparing
    state "จัดส่งแล้ว / Shipped" as Shipped
    state "สำเร็จ / Completed" as Completed
    state "ยกเลิก / Cancelled" as Cancelled
    Pending --> Preparing: Admin / Employee เริ่มจัดเตรียม
    Pending --> Cancelled: เจ้าของออเดอร์ หรือ Admin / Employee ยกเลิก
    Preparing --> Shipped: Admin / Employee และยืนยันรับเงินแล้ว
    Preparing --> Cancelled: Admin / Employee ยกเลิก
    Shipped --> Completed: เจ้าของออเดอร์ยืนยันรับสินค้า หรือเจ้าหน้าที่ปิดงาน
    Completed --> [*]
    Cancelled --> [*]
```

| สถานะปัจจุบัน | สถานะถัดไปที่อนุญาต | ผู้ดำเนินการ |
|---|---|---|
| รอดำเนินการ | กำลังจัดเตรียม | Admin / Employee |
| รอดำเนินการ | ยกเลิก | Admin / Employee / เจ้าของออเดอร์ |
| กำลังจัดเตรียม | จัดส่งแล้ว | Admin / Employee และต้องตรวจการชำระแล้ว |
| กำลังจัดเตรียม | ยกเลิก | Admin / Employee |
| จัดส่งแล้ว | สำเร็จ | Admin / Employee / เจ้าของออเดอร์ และต้องตรวจการชำระแล้ว |
| สำเร็จ / ยกเลิก | ไม่มี | ระบบไม่ให้ย้อนสถานะ |

หมายเลขติดตามพัสดุบันทึกโดย Admin/Employee ในออเดอร์ที่ยังไม่สำเร็จหรือยกเลิก และลูกค้าเปิดดูจากรายละเอียดออเดอร์ของตัวเอง

## 7. ยกเลิกออเดอร์และคืน Stock

```mermaid
flowchart TD
    A["ขอยกเลิกออเดอร์"] --> Begin["เริ่ม Transaction / ล็อกออเดอร์"]
    Begin --> Permission{"สิทธิ์และเจ้าของข้อมูลถูกต้อง?"}
    Permission -->|ไม่| Rollback["Rollback / แจ้งข้อผิดพลาด"]
    Permission -->|ใช่| Status{"สถานะนี้ยกเลิกได้?"}
    Status -->|ไม่| Rollback
    Status -->|ใช่| Restored{"stock_restored = 1 แล้ว?"}
    Restored -->|ไม่| Items["อ่านรายการสินค้า / ล็อกสินค้าแต่ละรายการ"]
    Items --> Restore["เพิ่ม Stock คืนตามจำนวนในออเดอร์"]
    Restore --> Ledger["บันทึกผู้ทำรายการ / จำนวน / เหตุผล / ออเดอร์อ้างอิง"]
    Ledger --> Flag["ตั้ง stock_restored = 1"]
    Restored -->|ใช่| Cancel["บันทึกสถานะ Cancelled"]
    Flag --> Cancel
    Cancel --> Audit["บันทึก audit_log"]
    Audit --> Commit["Commit"]
    Commit --> Done(["ยกเลิกสำเร็จ"])
```

ออเดอร์ที่ยกเลิกแล้วเป็นสถานะสุดท้าย การส่งคำขอซ้ำจึงถูกปฏิเสธ และมี `stock_restored` ช่วยป้องกันการคืน Stock ซ้ำ สำหรับออเดอร์ยกเลิกที่นำเข้าจากข้อมูลเก่า ระบบตั้งธงว่าจัดการ Stock แล้ว เพื่อไม่คืนสินค้าโดยไม่มีหลักฐานย้อนหลัง

## 8. ขายหน้าร้าน / POS

```mermaid
flowchart TD
    A["Admin หรือ Employee เปิด POS"] --> Find{"ค้นหาสินค้าด้วยวิธีใด?"}
    Find -->|พิมพ์ชื่อหรือรหัส| Search["ค้นหาในรายการสินค้า"]
    Find -->|กล้อง| Camera["สแกน QR"]
    Find -->|เครื่องสแกน| Hardware["อ่าน URL หรือ product_id / Enter"]
    Camera --> Parse["ตรวจ QR ของร้าน / อ่าน product_id"]
    Hardware --> Parse
    Search --> Select["เลือกสินค้าและจำนวน"]
    Parse --> Select
    Select --> Preview["แสดงราคาขายและคำนวณยอดรวม"]
    Preview --> Method["เลือกเงินสด / โอน / QR Payment"]
    Method --> Receive["พนักงานตรวจว่าได้รับเงินแล้ว"]
    Receive --> Confirm["ยืนยันการขาย"]
    Confirm --> Begin["เริ่ม Transaction"]
    Begin --> Server["ตรวจสิทธิ์และข้อมูล / ล็อกสินค้า / อ่านราคาจากฐานข้อมูลใหม่"]
    Server --> Enough{"Stock เพียงพอและจำนวนถูกต้อง?"}
    Enough -->|ไม่| Rollback["Rollback / ไม่บันทึกยอดขาย"]
    Enough -->|ใช่| Deduct["ตัด Stock / บันทึกประวัติ"]
    Deduct --> Save["บันทึก pos_sales และ pos_items / สถานะ Completed"]
    Save --> Snapshot["เก็บราคาขายและราคาทุน ณ เวลาขาย"]
    Snapshot --> Audit["บันทึกผู้ทำรายการและ audit_log"]
    Audit --> Commit["Commit"]
    Commit --> Receipt["แสดงรายละเอียด / พิมพ์รายการขาย"]
    Receipt --> Report["นำรายการสำเร็จเข้ารายงานยอดขาย"]
```

QR สินค้าใช้ค้นหาสินค้า ส่วน QR Payment ใช้ชำระเงิน เป็นข้อมูลคนละชุด พนักงานเห็นราคาขายและยอดรวม แต่ไม่เห็นทุนหรือกำไร การยืนยัน POS ถือว่าร้านได้รับเงินแล้ว ไม่มีการตรวจยอดกับธนาคารอัตโนมัติ

### แก้ไขการขายหน้าร้าน

```mermaid
flowchart TD
    A["Admin เปิดรายการขาย"] --> Edit["แก้สินค้า / จำนวน / วิธีชำระ / หมายเหตุ"]
    Edit --> Begin["เริ่ม Transaction / ล็อกรายการขาย"]
    Begin --> Old["คืน Stock ของรายการเดิม / บันทึกประวัติ"]
    Old --> Replace["ลบรายการย่อยเดิมภายใน Transaction"]
    Replace --> Check["ตรวจรายการใหม่และ Stock / อ่านราคาปัจจุบัน"]
    Check --> Valid{"รายการใหม่ถูกต้องและ Stock พอ?"}
    Valid -->|ไม่| Rollback["Rollback / คืนสถานะทุกอย่างกลับเป็นรายการเดิม"]
    Valid -->|ใช่| New["ตัด Stock ใหม่ / บันทึกรายการใหม่และราคาใหม่"]
    New --> Total["ปรับยอดรวม / วิธีชำระ / หมายเหตุ"]
    Total --> Audit["บันทึก audit_log และ Commit"]
```

Employee เพิ่มการขายได้ แต่แก้รายการขายเดิมไม่ได้ การแก้ POS โดย Admin คำนวณรายการใหม่ด้วยราคาสินค้าปัจจุบัน ส่วนการแก้ราคาสินค้าเพียงอย่างเดียวไม่เปลี่ยนราคาที่บันทึกไว้ในรายการขายเดิม

## 9. สินค้า / Stock / QR

```mermaid
flowchart TD
    Admin["Admin"] --> Product["เพิ่มหรือแก้สินค้า"]
    Product --> Data["ชื่อ / ประเภท / รายละเอียด / รูป / ราคาขาย / ราคาทุน / Stock / เกณฑ์แจ้งเตือน"]
    Data --> Validate{"ข้อมูลและรูปภาพถูกต้อง?"}
    Validate -->|ไม่| Error["แสดงข้อผิดพลาด"]
    Validate -->|ใช่| Save[("products")]
    Save --> Change{"มีการเพิ่มหรือปรับ Stock?"}
    Change -->|ใช่| Movement[("stock_movements")]
    Change -->|ไม่| Ready["พร้อมแสดงสินค้า"]
    Movement --> Ready
    Save --> QR["สร้าง QR จาก URL รายละเอียดและ product_id"]
    QR --> Print["ดูหรือพิมพ์ฉลาก"]
    Print --> Scan{"ใครสแกน?"}
    Scan -->|ลูกค้า| Detail["เปิดรายละเอียดสินค้า"]
    Scan -->|Admin / Employee ใน POS| POS["เพิ่มสินค้าเข้ารายการขาย"]
    Admin --> Archive["ลบ / ปิดขายสินค้า"]
    Archive --> Inactive["ตั้ง is_active = 0 / ซ่อนจากหน้าร้านและ POS"]
    Inactive --> History["เก็บสินค้าและประวัติการขายไว้"]
    Inactive --> Reactivate["Admin แก้ไขเพื่อเปิดขายอีกครั้ง"]
    Staff["Admin / Employee"] --> Receive["เลือกสินค้า / จำนวนรับเพิ่ม / เหตุผล"]
    Receive --> Lock["เริ่ม Transaction / ล็อกสินค้า"]
    Lock --> Add["เพิ่ม Stock"]
    Add --> Movement
    Ready --> Threshold{"Stock ไม่เกินเกณฑ์?"}
    Threshold -->|ใช่| Alert["แสดงสินค้าใกล้หมด / ค่าเริ่มต้น 5"]
    Threshold -->|ไม่| Available["พร้อมขาย"]
```

ประวัติ Stock เก็บ `product_id`, ผู้ทำรายการ, จำนวนเพิ่ม/ลด, ยอดหลังปรับ, เหตุผล, เอกสาร/รายการอ้างอิง และวันที่เวลา ทุกช่องทางใช้ Stock ของสินค้าเดียวกัน

## 10. ผู้ใช้งาน / พนักงาน / Role

```mermaid
flowchart TD
    A["Admin เปิดจัดการผู้ใช้งาน"] --> Choice{"เพิ่มหรือแก้ไข?"}
    Choice -->|เพิ่ม| New["กรอกข้อมูล / อีเมล / รหัสผ่าน / Role"]
    Choice -->|แก้ไข| Existing["เลือกบัญชี / แก้ข้อมูล / Role / สถานะ / รหัสผ่านใหม่ถ้าต้องการ"]
    New --> Check["ตรวจรูปแบบและอีเมลซ้ำ"]
    Existing --> Check
    Check --> Protect{"การเปลี่ยนบัญชีผ่านเงื่อนไขป้องกัน Admin?"}
    Protect -->|ไม่| Error["ปฏิเสธการเปลี่ยนแปลง"]
    Protect -->|ใช่| Hash["Hash รหัสผ่านหากมีการตั้งหรือเปลี่ยน"]
    Hash --> Save["บันทึก userdata / audit_log"]
    Save --> Active{"บัญชีเปิดใช้งาน?"}
    Active -->|ใช่| Next["สิทธิ์ครั้งถัดไปอ้างอิง Role ปัจจุบัน"]
    Active -->|ไม่| Block["ปฏิเสธการล็อกอิน / Session เดิมหมดสิทธิ์เมื่อเรียกหน้าถัดไป"]
```

Admin ไม่สามารถระงับหรือลดสิทธิ์บัญชี Admin ที่กำลังใช้งานอยู่ และระบบต้องมี Admin ที่เปิดใช้งานอย่างน้อยหนึ่งบัญชี ฟังก์ชันนี้ไม่ได้ลบบัญชีและประวัติการทำรายการ

## 11. ความคิดเห็นและคำตอบจากร้าน

```mermaid
flowchart TD
    User["ลูกค้าล็อกอิน"] --> Write["กรอกหัวข้อ / ความคิดเห็นหรือข้อเสนอแนะ"]
    Write --> Validate{"ข้อมูลครบและ CSRF ถูกต้อง?"}
    Validate -->|ไม่| Error["แจ้งข้อผิดพลาด"]
    Validate -->|ใช่| Save["บันทึก reports / user_id / status = new"]
    Save --> Admin["Admin เปิดอ่านความคิดเห็น"]
    Admin --> InProgress["เปลี่ยนเป็นกำลังดำเนินการได้"]
    InProgress --> Reply["เขียนคำตอบ"]
    Admin --> Reply
    Reply --> Status["เลือกสถานะ new / in_progress / resolved"]
    Status --> Resolved{"เลือก resolved?"}
    Resolved -->|ใช่| HasReply{"มีคำตอบ?"}
    HasReply -->|ไม่| Error
    HasReply -->|ใช่| Update["บันทึกคำตอบ / ผู้ตอบ / เวลาตอบ / สถานะ"]
    Resolved -->|ไม่| Update
    Update --> Own["ลูกค้าเปิดดูความคิดเห็นและคำตอบของตัวเอง"]
```

Employee ไม่มีสิทธิ์ใช้หน้าความคิดเห็นหรือตอบกลับลูกค้า

## 12. รายงานยอดขาย / ต้นทุน / กำไร / Stock

```mermaid
flowchart TD
    A["Admin เปิด Dashboard หรือรายงาน"] --> Period["เลือกวันเริ่มต้นและวันสิ้นสุด"]
    Period --> Valid{"วันที่ถูกต้องและเริ่มต้นไม่เกินสิ้นสุด?"}
    Valid -->|ไม่| Error["แจ้งข้อผิดพลาด"]
    Valid -->|ใช่| Sources{"แหล่งข้อมูล"}
    Sources --> Online["orders + order_items ที่ Completed"]
    Sources --> POS["pos_sales + pos_items ที่ Completed"]
    Online --> Revenue["รวมยอดขาย / แยกออนไลน์และหน้าร้าน"]
    POS --> Revenue
    Online --> Costs["อ่านราคาขายและ unit_cost ที่บันทึกในรายการ"]
    POS --> Costs
    Costs --> Known{"มีข้อมูลต้นทุน?"}
    Known -->|มี| Profit["ต้นทุน = จำนวน × ทุนต่อชิ้น / กำไร = จำนวน × ส่วนต่างราคา"]
    Known -->|ไม่มี| Unknown["แสดงไม่ทราบต้นทุน / ไม่นับในกำไรที่ทราบ"]
    Revenue --> Output["สรุปยอด / รายวัน / สินค้าขายดี / รายงานแยกช่องทาง"]
    Profit --> Output
    Unknown --> Output
    Sources --> Ledger["stock_movements"]
    Ledger --> Balance["เลือกยอดหลังปรับล่าสุดไม่เกินวันสิ้นสุด"]
    Balance --> StockReport["รายงาน Stock ณ วันที่เลือก"]
    StockReport --> NoHistory{"มีประวัติถึงวันนั้น?"}
    NoHistory -->|ไม่มี| UnknownStock["แสดงไม่มีประวัติ"]
    NoHistory -->|มี| ShowStock["แสดงคงเหลือ / ใกล้หมด / มูลค่าที่ทุนปัจจุบัน"]
    Output --> Print["พิมพ์รายงาน / บันทึก PDF ผ่านเบราว์เซอร์"]
    ShowStock --> Print
```

หลักการคำนวณ:

- **ยอดขาย:** ยอดรวมของรายการสำเร็จ ภายในช่วงวันที่ของรายการขาย (`order_date` สำหรับออนไลน์ และ `created_at` สำหรับ POS)
- **ต้นทุนที่ทราบ:** ผลรวม `quantity × unit_cost` เฉพาะรายการที่มีต้นทุน
- **กำไรที่ทราบ:** ผลรวม `quantity × (price − unit_cost)` เฉพาะรายการที่มีต้นทุน เป็นกำไรจากส่วนต่างสินค้า ยังไม่หักค่าเช่า ค่าแรง หรือค่าใช้จ่ายอื่น
- **สินค้าขายดี:** รวมจำนวนขายจากทั้งสองช่องทาง เฉพาะรายการสำเร็จ
- **Stock ตามวันที่:** ใช้ยอดคงเหลือจากประวัติ Stock ณ สิ้นสุดวันที่เลือก
- **มูลค่าสินค้าคงเหลือ:** ใช้จำนวนตามรายงานคูณราคาทุนปัจจุบัน จึงไม่ใช่มูลค่าทุนย้อนหลัง ณ วันที่เลือก
- **ข้อมูลเดิม:** ไม่มีการเดาราคาทุนย้อนหลัง วันที่ก่อนเริ่มเก็บประวัติ Stock จะแสดงว่าไม่มีประวัติ

## 13. การเชื่อมโยงข้อมูล

แผนภาพนี้เป็นความสัมพันธ์เชิงตรรกะของระบบ ไม่ได้หมายความว่าทุกเส้นมี Foreign Key บังคับในฐานข้อมูล

```mermaid
erDiagram
    USERDATA ||--o{ CART : owns
    PRODUCTS ||--o{ CART : selected
    USERDATA ||--o{ ORDERS : places
    ORDERS ||--|{ ORDER_ITEMS : contains
    PRODUCTS ||--o{ ORDER_ITEMS : sold_online
    USERDATA ||--o{ POS_SALES : operates
    POS_SALES ||--|{ POS_ITEMS : contains
    PRODUCTS ||--o{ POS_ITEMS : sold_in_store
    PRODUCTS ||--o{ STOCK_MOVEMENTS : tracks
    USERDATA o|--o{ STOCK_MOVEMENTS : performs
    USERDATA ||--o{ REPORTS : submits
    USERDATA o|--o{ REPORTS : replies
    USERDATA ||--o{ AUDIT_LOG : performs

    USERDATA {
        int user_id PK
        string email
        string password
        enum role
        boolean is_active
    }
    PRODUCTS {
        int product_id PK
        string product_name
        string product_price
        decimal cost_price
        int stock
        int low_stock_threshold
        boolean is_active
    }
    ORDERS {
        int order_id PK
        int user_id
        string order_code
        decimal total_price
        string order_status
        string payment_status
        string payment_slip
        string transport_id
        boolean stock_restored
    }
    ORDER_ITEMS {
        int order_item_id PK
        int order_id
        int product_id
        int quantity
        decimal price
        decimal unit_cost
    }
    POS_SALES {
        int id PK
        int actor_id
        string sale_code
        decimal total
        string payment_method
        string status
    }
    POS_ITEMS {
        int id PK
        int sale_id
        int product_id
        int quantity
        decimal price
        decimal unit_cost
    }
    STOCK_MOVEMENTS {
        int id PK
        int product_id
        int actor_id
        int delta
        int balance
        string reason
        string reference_code
        datetime created_at
    }
    REPORTS {
        int report_id PK
        int user_id
        string issue_title
        string issue_description
        string admin_response
        string status
        int replied_by
    }
    AUDIT_LOG {
        int id PK
        int actor_id
        string action
        string details
        datetime created_at
    }
```

`shop_settings` เก็บข้อมูลบัญชีธนาคารและภาพ QR รับเงิน ส่วน QR สินค้าสร้างจาก URL และ `product_id` โดยไม่ต้องมีตาราง QR แยก `product_price` ในฐานข้อมูลเดิมเป็น VARCHAR แต่ระบบตรวจเป็นจำนวนเงินและคำนวณการขายด้วยหน่วยสตางค์

## 14. หน้าจอและสิทธิ์

| งาน | Admin | Employee | User / ผู้เยี่ยมชม |
|---|---|---|---|
| สินค้าและ QR สำหรับดูรายละเอียด | ได้ | ได้ | ได้โดยไม่ต้องล็อกอิน |
| Dashboard | ของ Admin | ของ Employee | ไม่มี |
| จัดการสินค้า / ทุน / ราคา / พิมพ์ QR | ได้ | ไม่ได้ | ไม่ได้ |
| POS ใหม่ | ได้ | ได้ | ไม่ได้ |
| แก้ POS เดิม | ได้ | ไม่ได้ | ไม่ได้ |
| ประวัติ POS | ทั้งร้าน | รายการที่ตัวเองทำ | ไม่ได้ |
| Stock / รับสินค้า / ประวัติ | ได้ | ได้ | ดูเฉพาะจำนวนพร้อมขายในหน้าสินค้า |
| ออเดอร์ออนไลน์ของร้าน / ตรวจเงิน / เตรียม / จัดส่ง | ได้ | ได้ | ไม่ได้ |
| ซื้อสินค้าและติดตามออเดอร์ของตัวเอง | ได้ | ได้ | ต้องล็อกอิน |
| ความคิดเห็น | จัดการและตอบ | ไม่ได้ | ส่งและดูของตัวเองเมื่อล็อกอิน |
| ผู้ใช้งาน / Role / ระงับบัญชี | ได้ | ไม่ได้ | ไม่ได้ |
| รายงานทุนและกำไร / ตั้งค่ารับเงิน | ได้ | ไม่ได้ | ไม่ได้ |
| โปรไฟล์ / เปลี่ยนรหัสผ่าน | ของตัวเอง | ของตัวเอง | ของตัวเองเมื่อล็อกอิน |

| Role / งาน | URL |
|---|---|
| เข้าสู่ระบบ | `/login.php` |
| สมัครสมาชิก | `/register.php` |
| สินค้า / รายละเอียด / สแกน | `/products.php`, `/product_content.php?id=...`, `/scan.php` |
| ตะกร้า | `/cart.php` |
| ออเดอร์ลูกค้า | `/order.php`, `/order_detail.php?order_id=...` |
| ความคิดเห็นลูกค้า | `/report_user.php` |
| โปรไฟล์ | `/profile.php` |
| Dashboard Admin / Employee | `/admin/dashboard.php`, `/employee/dashboard.php` |
| POS Admin / Employee | `/admin/pos.php`, `/employee/pos.php` |
| การขายหน้าร้าน | `/admin/sales.php`, `/employee/sales.php` |
| ออเดอร์ร้าน | `/admin/orders.php`, `/employee/orders.php` |
| Stock / ประวัติ | `/admin/stock.php`, `/employee/stock.php`, `/admin/stock_history.php`, `/employee/stock_history.php` |
| จัดการสินค้า / QR | `/admin/products.php`, `/admin/qr.php` |
| ผู้ใช้งาน | `/admin/users.php` |
| ความคิดเห็นร้าน | `/admin/feedback.php` |
| รายงาน / ตั้งค่ารับเงิน | `/admin/reports.php`, `/admin/settings.php` |

## 15. ตัวอย่าง Stock และกำไร

สมมติสินค้า A มี Stock 20 ชิ้น ราคาขาย 100 บาท และทุน 60 บาท

| เหตุการณ์ | จำนวนเปลี่ยน | Stock หลังเหตุการณ์ |
|---|---:|---:|
| รับสินค้าเพิ่ม | +10 | 30 |
| ลูกค้าเพิ่มตะกร้า 3 ชิ้น | 0 | 30 |
| ลูกค้ายืนยันสั่งซื้อ 3 ชิ้น | -3 | 27 |
| พนักงานขาย POS 2 ชิ้น | -2 | 25 |
| ยกเลิกออเดอร์ออนไลน์ 3 ชิ้น | +3 | 28 |
| ส่งคำขอยกเลิกเดิมซ้ำ | 0 | 28 |

หลังยกเลิกออนไลน์ เหลือยอดขายสำเร็จจาก POS 200 บาท ต้นทุน 120 บาท และกำไรจากสินค้า 80 บาท ออเดอร์ออนไลน์ที่ถูกยกเลิกไม่รวมในยอดขายสำเร็จ
