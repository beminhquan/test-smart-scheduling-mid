# GOOGLE STITCH MASTER PROMPT SUITE — SMART SCHEDULING SYSTEM
> **Project:** Smart Scheduling (Hệ thống Xếp lịch và Quản lý Ca làm việc Thông minh)  
> **Source Directory:** `d:\Application\xampp\htdocs\SMART- SCHEDULING2\smart-scheduling`  
> **Target Tool:** Google Stitch (`stitch.withgoogle.com`) -> Export to Figma  
> **Design Standard:** 100% Faithful to real source code implementation (No invented features/screens)  
> **Language Standard:** Layout & design instructions in 100% English; ALL visible UI texts, buttons, placeholders, badges, menu labels, sample records in EXACT VIETNAMESE WITH ACCENTS in double quotes.

---

## TABLE OF CONTENTS (MỤC LỤC DANH SÁCH MÀN HÌNH)

### PHẦN I – MASTER DESIGN SYSTEM & TOKENS
1. Color Palette & Exact HEX Tokens
2. Typography & Vietnamese Font Configuration
3. Radius, Shadows, Spacing & Border Standards
4. Wage Rates, Currencies & Business Data Rules
5. Standard Layout Architecture (Header, Sidebar & Content Containers)
6. Anti-Layout-Breaking Rules for Google Stitch & Figma Export

### PHẦN II – DANH SÁCH MASTER PROMPTS (GD-00 ĐẾN GD-27)

#### NHÓM 1: XÁC THỰC & ĐĂNG NHẬP (COMMON / AUTH FLOW)
- **GD-00:** Đăng nhập hệ thống & Khung minh họa tính năng (`/login`)

#### NHÓM 2: CỔNG QUẢN LÝ (MANAGER FLOW)
- **GD-01:** Tổng quan điều hành – Dashboard Quản lý (`/manager/dashboard`)
- **GD-02:** Ca & Ràng buộc – Cấu hình ca mẫu & Quy tắc (`/manager/shift-config`)
- **GD-03:** Khả năng làm việc – Ma trận khảo sát lịch rảnh tuần (`/manager/availability`)
- **GD-04:** Tạo lịch tự động – Cấu hình định biên & OR-Tools (`/manager/schedule`)
- **GD-05:** Lịch đã tạo – Phân công ca trực theo tuần (Lưới & Danh sách) (`/manager/schedule/created`)
- **GD-06:** Đổi ca – Quản lý đối soát & Phê duyệt yêu cầu đổi ca (`/manager/shift-exchange`)
- **GD-07:** Ca trực mở – Quản lý ca trống & Đăng ký tăng cường (`/manager/open-shifts`)
- **GD-08:** Quản lý nhân viên – Hồ sơ nhân sự, hợp đồng & mức lương (`/manager/employees`)
- **GD-09:** Đơn xin nghỉ – Phê duyệt đơn nghỉ phép & AI gợi ý thay thế (`/manager/leave`)
- **GD-10:** Chấm công – Đối soát giờ vào/ra thực tế & Điểm danh (`/manager/attendance`)
- **GD-11:** Giờ làm – Tổng hợp giờ làm việc thực tế & Tỷ lệ kế hoạch (`/manager/work-hours`)
- **GD-12:** Tính lương – Bảng lương tổng hợp & Chốt sổ kỳ lương (`/manager/payroll`)
- **GD-13:** Báo cáo – Tổng quan báo cáo điều hành & Xuất Excel (`/manager/reports`)
- **GD-14:** Trợ lý AI – Trợ lý hội thoại phân tích ca & Vận hành (`/manager/assistant`)

#### NHÓM 3: CỔNG NHÂN VIÊN (EMPLOYEE FLOW)
- **GD-15:** Tổng quan nhân viên (`/employees/Tong-quan`)
- **GD-16:** Lịch của tôi – Lịch làm việc cá nhân (`/employees/schedule`)
- **GD-17:** Đăng ký thời gian rảnh – Khảo sát rảnh/bận 7 ngày (`/employees/availability`)
- **GD-18:** Đổi ca nhân viên – Hub đổi ca, hoán đổi & nhờ làm thay (`/employees/shift-exchange`)
- **GD-19:** Ca trực mở nhân viên – Nhận ca tăng cường (`/employees/open-shifts`)
- **GD-20:** Nghỉ phép nhân viên – Nộp đơn xin nghỉ đột xuất (`/employees/leave`)
- **GD-21:** Chấm công nhân viên – Check-in / Check-out ca làm (`/employees/attendance`)
- **GD-22:** Thông tin cá nhân – Hồ sơ nhân viên (`/employees/profile`)

#### NHÓM 4: CÁC MODAL NGHIỆP VỤ PHỨC TẠP (STANDALONE MODAL FRAMES)
- **GD-23:** Modal Tạo lịch tự động OR-Tools & Điều chỉnh ca trực (Kèm tại `/manager/schedule`)
- **GD-24:** Modal Điều chỉnh chấm công & Lịch sử sửa đổi (Kèm tại `/manager/attendance`)
- **GD-25:** Modal Phiếu lương chi tiết nhân viên (Kèm tại `/manager/payroll`)
- **GD-26:** Modal Chi tiết đơn nghỉ & Bảng AI gợi ý người thay ca (Kèm tại `/manager/leave`)
- **GD-27:** Modal Tạo yêu cầu đổi ca nhân viên (Kèm tại `/employees/shift-exchange`)

### PHẦN III – HƯỚNG DẪN SỬ DỤNG (WORKFLOW GOOGLE STITCH & FIGMA)

---

# PHẦN I – MASTER DESIGN SYSTEM & TOKENS (TRÍCH XUẤT TỪ CODE THỰC TẾ)

### 1. BẢNG MÀU CHUẨN DESIGN TOKENS (EXACT HEX CODES & ROLES)
- **Primary Navy (`--navy`):** `#0F172A` (Nút hành động chính, tiêu đề trang, text chính, icon chính).
- **Primary Hover:** `#1E293B` (Trạng thái hover của nút chính).
- **Active Background (`--active-bg`):** `#CBE3F0` (Nền mục menu đang chọn, tab segmented SubNav/StatusTabs đang chọn, ô chứa icon trang, avatar initials).
- **Highlight Highlight (`--highlight`):** `#D9E8F5` (Nền thanh "Ca tiếp theo", cột "Hôm nay" trong lịch tuần, badge vai trò Logo).
- **Page Background (`--page-bg`):** `#F4F7F9` (Nền panel làm việc bên phải).
- **Shell Background (`--shell-bg`):** `#DCEBF5` (Nền ngoài cùng khung vỏ ứng dụng App Shell).
- **Card & Container Surface:** `#FFFFFF` (Bo góc lớn `rounded-2xl`, viền mảnh `border-slate-200/90`, bóng nhẹ `shadow-2xs`).
- **Sidebar Backgrounds:**
  - Manager & Employee Sidebar: Nền trắng sạch `#FFFFFF`, viền mảnh `border border-slate-200/90`, bo góc lớn `rounded-2xl` nổi trên nền `--shell-bg: #DCEBF5`. (Bỏ hoàn toàn gradient cũ).
- **Shift Colors (Theo Ca làm việc):**
  - **Ca Sáng:** Nền `#FFF6E0`, viền `#D98A00`, chấm `#F59E0B`, chữ `#78350F`, badge `#FDE68A`.
  - **Ca Chiều:** Nền `#E6F6F3`, viền `#0F9E8A`, chấm `#10B981`, chữ `#064E3B`, badge `#A7F3D0`.
  - **Ca Tối:** Nền `#F1EDFC`, viền `#7C5CD6`, chấm `#8B5CF6`, chữ `#4C1D95`, badge `#DDD6FE`.
- **Functional Status Colors (5 Tone Chuẩn):**
  - **Success (Đã duyệt / Đúng giờ / Đang làm việc / Hoàn thành):**
    Dot & Icon: `#10B981` | Nền: `#ECFDF5` (`bg-emerald-50`) | Chữ: `#047857` (`text-emerald-700`) | Viền: `#A7F3D0`
  - **Warning (Chờ duyệt / Đi trễ / Ca còn thiếu / Sắp tới):**
    Dot & Icon: `#F59E0B` | Nền: `#FFFBEB` (`bg-amber-50`) | Chữ: `#B45309` (`text-amber-700`) | Viền: `#FDE68A`
  - **Danger / Error (Bị từ chối / Vắng mặt / Quên checkout / Nghỉ việc / Nút xóa):**
    Dot & Nút: `#D64550` / `#E5484D` | Nền: `#FEF2F2` (`bg-rose-50`) | Chữ: `#B91C1C` (`text-rose-700`) | Viền: `#FECDD3`
  - **Info / Pending (Chờ đồng nghiệp / Ca mở / Đang diễn ra):**
    Dot & Icon: `#3B82F6` | Nền: `#EFF6FF` (`bg-blue-50`) | Chữ: `#1D4ED8` (`text-blue-700`) | Viền: `#BFDBFE`
  - **Neutral / Draft (Nghỉ phép / Đã hủy / Chưa diễn ra):**
    Dot: `#94A3B8` | Nền: `#F1F5F9` (`bg-slate-100`) | Chữ: `#475569` (`text-slate-600`) | Viền: `#CBD5E1`

### 2. TYPOGRAPHY & PHÔNG CHỮ TIẾNG VIỆT
- **Font Family Chuẩn:** `"Be Vietnam Pro", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif` (Hỗ trợ 100% tiếng Việt có dấu, hiển thị sắc nét trên cả màn hình retina và tiêu chuẩn).
- **Cỡ chữ & Kiểu dáng đang sử dụng:**
  - Login Hero Heading: `28px`, Font Weight: `700` (Bold), Line Height: `1.2`
  - Modal Heading & Showcase Subtitle: `20px` - `26px`, Font Weight: `700` (Bold)
  - Page Title (h1 trong PageHeader): `22px`, Font Weight: `700` (Bold), Color: `#0F172A`
  - Card Title: `16px`, Font Weight: `600` (SemiBold), Color: `#0F172A`
  - Subtitle / Description (hàng 2): `13.5px` - `14px`, Font Weight: `400` (Regular), Color: `#64748B`
  - Stat KPI Numbers: `24px` (hoặc `20px` dạng compact), Font Weight: `700` (Bold), `tabular-nums`
  - Stat KPI Labels: `11.5px`, Font Weight: `600` (SemiBold), UPPERCASE
  - Table Header & Th: `13px` - `13.5px`, Font Weight: `500` - `600`, Color: `#475569`
  - Calendar Day Headers: `13.5px` - `15px`, Font Weight: `400` (Regular, KHÔNG in đậm)
  - Table Cell Text: `13.5px` - `14px`, Font Weight: `400` (Regular), Color: `#1E293B`
  - Menu Link Text: `14px`, Font Weight: `500` (Active: `600`)
  - Badges / Status Pills: `10.5px` - `12px`, Font Weight: `500` (Medium)

### 3. RADIUS, SHADOW, SPACING & BORDER
- **Border Radius:**
  - App Shell Container: Bo góc `rounded-2xl` cho cả Sidebar và Panel làm việc
  - PageHeader Card: `rounded-2xl` (`16px`), viền `border-slate-200/90`
  - Stat KPI Cards: `rounded-2xl` (`16px`)
  - Table Wrapper & Modal: `rounded-2xl` (`16px`)
  - Form Input & Filter Select: `rounded-xl` (`12px`)
  - Buttons (Chính & Phụ): `rounded-xl` (`12px`)
  - Menu Items: `rounded-xl` (`12px`)
  - Status Badges / Pills: `rounded-full` (`9999px`)
- **Shadows:**
  - PageCard & Stat Card: `shadow-2xs`
  - Modal Dialog: `shadow-lg`
  - Active Menu Pill: `shadow-2xs`
- **Border Thickness:** Đều `1px` đồng nhất, ngoại trừ focus-visible (`2px` outline).

### 4. QUY TẮC DỮ LIỆU THỰC TẾ & MẪU SỐ LIỆU TRONG CODE
- **Đơn vị tiền tệ:** Việt Nam Đồng (`₫` hoặc `đ`). Hiển thị dạng định dạng hàng nghìn có dấu chấm: `35.000 đ/h`, `50.000 ₫`, `3.650.000 ₫`.
- **4 Chuyên môn & Mã Role_Tag:**
  - `Shift_Leader` -> "Quản lý ca"
  - `Barista` -> "Pha chế"
  - `Cashier` -> "Thu ngân"
  - `Waiter` -> "Phục vụ"
- **Loại hợp đồng:** "Toàn thời gian" (Full-time) và "Bán thời gian" (Part-time).
- **Khung ca chuẩn:**
  - "Ca sáng": `06:00 - 12:00` (6 giờ)
  - "Ca chiều": `12:00 - 17:00` (5 giờ)
  - "Ca tối": `17:00 - 22:00` (5 giờ, hệ số ca đêm x1.2)
- **Danh sách nhân sự mẫu (Seed Roster):**
  - `NV001`: "Nguyễn Văn An" | "Quản lý ca" | "Toàn thời gian" | "35.000 đ/h" | "Đang làm việc"
  - `NV002`: "Trần Thị Bích" | "Pha chế" | "Toàn thời gian" | "30.000 đ/h" | "Đang làm việc"
  - `NV003`: "Lê Minh Cường" | "Thu ngân" | "Bán thời gian" | "28.000 đ/h" | "Đang làm việc"
  - `NV004`: "Phạm Thị Dung" | "Pha chế" | "Bán thời gian" | "30.000 đ/h" | "Nghỉ phép"
  - `NV005`: "Hoàng Văn Nam" | "Thu ngân" | "Bán thời gian" | "28.000 đ/h" | "Đang làm việc"
  - `NV006`: "Đỗ Kim Ngân" | "Phục vụ" | "Bán thời gian" | "25.000 đ/h" | "Nghỉ việc" (loại trừ khỏi xếp lịch)
  - `NV007`: "Vũ Minh Thảo" | "Pha chế" | "Bán thời gian" | "30.000 đ/h" | "Đang làm việc"
  - `NV008`: "Đặng Hoàng Yến" | "Phục vụ" | "Bán thời gian" | "28.000 đ/h" | "Đang làm việc"

### 5. KIẾN TRÚC LAYOUT CHUẨN DESIGN TOKENS 2026 (APPSHELL CỐ ĐỊNH)
- **Cấu trúc Khung Vỏ Ứng Dụng (AppShell):**
  - Chiều cao 100vh cố định: `h-screen w-screen overflow-hidden`.
  - Nền ngoài cùng là xanh pastel `--shell-bg: #DCEBF5` có padding quanh mép (`p-2.5 sm:p-3.5`).
  - Menu bên trái là card trắng bo góc lớn (`rounded-2xl border border-slate-200/90 shadow-sm`), nổi bật độc lập.
  - Panel làm việc bên phải là card bo góc lớn nền `#F4F7F9` (`rounded-2xl border border-slate-200/90 shadow-sm flex flex-col overflow-hidden`):
    + Header cố định ở trên (`h-[64px] shrink-0 bg-white/80 backdrop-blur-md border-b border-slate-200/70`).
    + Vùng nội dung bên dưới cuộn độc lập (`flex-1 overflow-y-auto px-4 py-5 sm:px-6 sm:py-6`).
    + Body hoàn toàn KHÔNG CÓ thanh cuộn.
- **Thanh bên Menu (Sidebar):**
  - Chiều rộng `264px` (thu gọn `80px`), nền trắng sạch `bg-white`, bo góc `rounded-2xl`.
  - Logo trên cùng: Ô vuông 40x40px nền `#CBE3F0` chứa logo, tên Smart Scheduling màu `#0F172A` và badge vai trò "Staff" hoặc "Quản lý" nền `#D9E8F5` thẳng hàng.
  - Mục menu active: Nền `--active-bg: #CBE3F0`, chữ `#0F172A` font-medium, bo góc `rounded-xl`.
  - Dưới cùng: Avatar tròn initials, tên, vai trò và nút "Đăng xuất". Bỏ hoàn toàn chuyển vai.
- **Thanh đầu trang (Top Header):**
  - Chiều cao `64px`, cố định dính trên cùng, nền `bg-white/80 backdrop-blur-md border-b border-slate-200/70`.
  - Bên trái: LiveClock định dạng tiếng Việt "Thứ Tư, 28/10/2026 · 19:10" và nút thu gọn menu.
  - Ở giữa: Ô tìm kiếm bo tròn hoàn toàn `rounded-full bg-white border border-slate-200`.
  - Bên phải: Chuông thông báo (NotificationBell), Avatar tròn initials nền `#CBE3F0` chữ `#0F172A` + Tên người dùng.
- **Tiêu đề trang (PageHeader):** Nằm trong 1 card trắng riêng biệt (`rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs`), có breadcrumb nhỏ phía trên, icon trang trong ô `#CBE3F0` bên trái tiêu đề, tiêu đề ~22px, mô tả ngắn xám, nút hành động bên phải.
- **Khung thống kê (StatStrip / KPI Cards):** Lưới các card nhỏ trắng riêng rẽ `rounded-2xl`, nhãn uppercase, số lớn tabular-nums, icon trong ô `#CBE3F0` ở góc phải.
- **Trang Đăng Nhập:** Nền xanh pastel `#DCEBF5`, card trắng bo góc lớn `rounded-2xl sm:rounded-3xl` ở chính giữa màn hình, tự nhập tài khoản/mật khẩu, nút navy `#0F172A`.

### 6. QUY TẮC CHỐNG VỠ BỐ CỤC TRÊN GOOGLE STITCH & FIGMA
- Khung trang luôn đặt `w-full max-w-[1440px] mx-auto`.
- Bảng hiển thị luôn bọc trong thẻ `overflow-x-auto rounded-xl border border-slate-200`, thuộc tính bảng `w-full table-fixed text-[14px]`. Tổng tỷ lệ phần trăm các cột phải đúng bằng 100%.
- Các nút bấm (`Button`), badge (`StatusBadge`), dropdown filter luôn mang class `whitespace-nowrap` để chống tràn dòng khi co kéo khung trong Figma.
- Vùng bản đồ, biểu đồ tương tác, canvas trực quan được mô tả là `UI Placeholder Frame` với nhãn chú thích rõ ràng.

---

# PHẦN II – DANH SÁCH MASTER PROMPTS (GD-00 ĐẾN GD-27)

---

### GD-00: Đăng nhập hệ thống & Khung minh họa tính năng
* **Screen ID:** `GD-00` | **Actor:** `All Users (Chung cho Quản lý & Nhân viên)`
* **Context:** Màn hình xác thực đầu vào của toàn bộ ứng dụng (`/login`). Người dùng truy cập khi chưa đăng nhập hoặc khi phiên làm việc hết hạn (`/login?expired=1`). Nhập email/mật khẩu để chuyển hướng tới portal Quản lý hoặc Nhân viên.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-00] Login Screen & System Showcase (/login)
A full-screen split layout (1440x900px desktop screen) with two equal vertical columns. The left column contains the clean white login card and authentication form, while the right column contains an aesthetic light-blue decorative showcase highlighting core scheduling automation value props.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Responsive (Split 50/50 on desktop, full-width on mobile)
- Font: "Be Vietnam Pro", sans-serif
- Colors: White #FFFFFF, Primary Brand #3F62C4, Primary Hover #3452A8, Dark Text #0E1A3A, Subtitle #62718F, Border #DCE6F5, Input Background #F7FAFF, Showcase Gradient linear-gradient(160deg, #C7DCFF 0%, #DDEAFF 45%, #F0F6FF 100%)
- Card Corner Radius: 16px (rounded-2xl) on badges/floating cards, 12px (rounded-xl) on inputs/buttons.

PAGE COMPOSITION & TEXT COPY:
1. LEFT COLUMN: AUTHENTICATION FORM (Width: 50%, White Background #FFFFFF, padding 48px):
   - Top Header: Brand Logo with clock hourglass icon and dual text "Smart" (in #0E1A3A) and "Scheduling" (in #3F62C4).
   - Form Container (Centered, max-width 400px):
     - Heading h1: "Đăng nhập" (28px bold, #0E1A3A)
     - Subtitle paragraph: "Dùng email và mật khẩu được cấp để vào không gian làm việc của bạn." (14px, #62718F)
     - Expired Session Alert (Conditional Banner): Sky blue banner with info icon: "Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại"
     - Error Alert Banner (Conditional when login fails): Soft red banner with alert icon: "Email hoặc mật khẩu không đúng, hoặc tài khoản không còn hoạt động."
     - Form Fields:
       * Label 1: "Email" (13px font-medium #3B4C70)
         Input: Placeholder "ten@congty.com", background #F7FAFF, border #DCE6F5, height 44px, rounded-xl.
         Under-input error message: "Vui lòng nhập email." or "Email không đúng định dạng."
       * Label 2: "Mật khẩu" (13px font-medium #3B4C70)
         Input: Password input with eye toggle icon button on the right, placeholder "Nhập mật khẩu", height 44px.
         Under-input error message: "Vui lòng nhập mật khẩu."
       * Submit Button: Full-width button, background #3F62C4, text white, height 44px, rounded-xl, font-semibold 15px: "Đăng nhập" (Loading state: "Đang đăng nhập…").
     - Account Support Note: "Chưa có tài khoản? Liên hệ Quản lý để được cấp tài khoản." (13px centered, #62718F)
     - Mock Mode Guidance Card (Dashed border #C9D8F0, bg #F7FAFF, padding 12px, rounded-xl):
       "Chế độ mock: email bắt đầu bằng "manager" = Manager, còn lại = Staff. Mật khẩu nhập gì cũng được."
   - Bottom Footer: Centered text "© Smart Scheduling" (12px, #8795B1).

2. RIGHT COLUMN: FEATURE SHOWCASE (Width: 50%, Background gradient from #C7DCFF to #F0F6FF, grid pattern overlay with 56px squares):
   - Showcase Heading: "Xếp ca thông minh, vận hành nhẹ nhàng" (26px bold #0E1A3A, centered).
   - Showcase Subtitle: "Tự động xếp lịch theo ràng buộc, đối soát chấm công và tính lương chính xác." (14px #4A5B80, centered).
   - Interactive Visual Floating Cards (Stacked UI illustration):
     * Card A (Dark Navy Floating Card #0F172A, width 280px, padding 20px, rounded-2xl, shadow-xl):
       - Label: "Tỷ lệ lấp đầy ca" (12px #94A3B8)
       - Big KPI Metric: "92%" (34px bold white)
       - Progress bar: Width 100%, background white/15%, filled 92% with sky blue #38BDF8.
       - Sub-metric: "Tăng 3% so với tuần trước" (12px #94A3B8).
     * Card B (White Floating AI Card, width 250px, padding 16px, rounded-2xl, shadow-xl):
       - Label: "Gợi ý của AI" (12px #64748B)
       - Alert headline: "Ca Chiều thứ Tư thiếu 1 người" (14px font-semibold #0F172A)
       - Overlapping employee avatars: 3 circles with initials "AN", "BT", "CM", followed by a "+3" blue pill.
     * Card C (White Floating Weekly Bar Chart Card, width 320px, padding 16px, rounded-2xl, shadow-xl):
       - Label: "Số ca trong tuần" (12px #64748B)
       - 7 vertical bar indicators labeled "T2", "T3", "T4", "T5", "T6", "T7", "CN". Thursday bar is highlighted with blue gradient.

STRICT NEGATIVE CONSTRAINTS:
- Do NOT include any top navigation bar, tabs or sidebar in this login screen.
- Do NOT translate any Vietnamese text strings into English.
- No social login buttons (Google, Facebook) since this is an internal enterprise staff system.
```

---

### GD-01: Tổng quan điều hành – Dashboard Quản lý
* **Screen ID:** `GD-01` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình trung tâm sau khi đăng nhập của Quản lý (`/manager/dashboard`). Cung cấp chỉ số vận hành tổng quan, việc cần xử lý ngay, ca trực thiếu người và các biểu đồ KPI chi phí, chấm công.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-01] Manager Operations Dashboard (/manager/dashboard)
Complete desktop view (1440px wide) featuring standard Manager Layout: Left Sidebar (width 272px, light gradient background), Top Header (height 72px with live clock and search), and a PageCard container displaying operational KPIs, actionable to-dos, understaffed alerts, and summary charts.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Font: "Be Vietnam Pro", sans-serif
- Colors: Main Background #F2F6FC, Sidebar Gradient linear-gradient(180deg,#C7DCFF 0%,#DDEAFF 38%,#F0F6FF 100%), Card White #FFFFFF, Primary #3F62C4, Text Dark #0E1A3A, Border #E2E8F0.
- Layout: 272px fixed sidebar, 72px sticky header, 1440px max-width central container.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR (Width 272px):
   - Logo at top: Hourglass icon + "Smart" (#0E1A3A) + "Scheduling" (#3F62C4).
   - Menu Items with active highlight on "Tổng quan":
     * Group "Chính": "Tổng quan" (Active pill: white background, blue text #3F62C4, shadow-sm).
     * Group "Lịch và ca": "Ca & ràng buộc", "Khả năng làm việc", "Lịch làm việc" (dropdown), "Đổi ca", "Ca trực mở".
     * Group "Nhân sự": "Quản lý nhân viên", "Đơn xin nghỉ", "Chấm công & Giờ làm" (dropdown).
     * Group "Tài chính": "Tính lương", "Báo cáo".
     * Group "Công cụ": "Trợ lý AI".
   - Bottom User Card: Avatar circle "NA", User name: "Nguyễn Văn An", role: "Quản lý", reset demo button with tooltip "Đặt lại dữ liệu demo", logout icon button with tooltip "Đăng xuất".

2. TOP HEADER (Height 72px, Sticky):
   - Left: Greeting "Xin chào, Nguyễn Văn An", Live clock: "Thứ Hai, 27 tháng 10 năm 2026, 22:00:00".
   - Right: Search input with placeholder "Tìm nhân viên, ca làm, đơn từ...", notification bell button, primary CTA button with sparkles icon: "Xếp lịch tự động" (links to GD-04).

3. PAGECARD CONTENT (Rounded-2xl, White Background, Border #E2E8F0):
   - Header Row 1: Title "Tổng quan" (18px font-semibold #0E1A3A). Right action: Period picker dropdown with value "Tuần này" and date label "20/10/2026 – 26/10/2026".
   - Header Row 2: Subtitle "Theo dõi giờ làm, chi phí lương, chấm công và ca còn thiếu người." (14px #64748B).
   - StatStrip (5 KPI Cards row with dividing borders):
     * Card 1: Users icon | Label: "Tổng nhân viên" | Value: "7" | Subtext: "Đang hoạt động"
     * Card 2: Percentage icon | Label: "Tỷ lệ lấp đầy" | Value: "91.7%" | Subtext: "Đạt mục tiêu"
     * Card 3: Wallet icon | Label: "Chi phí lương" | Value: "18.450.000 ₫" | Subtext: "Tuần này"
     * Card 4: Clock icon | Label: "Đi trễ" | Value: "2 ca" | Subtext: "Chiếm 4.8%" (Amber tag)
     * Card 5: UserX icon | Label: "Vắng mặt" | Value: "1 ca" | Subtext: "Đã có người thay" (Rose tag)
   - Filter Toolbar:
     * Dropdown 1: "Tất cả ca" (Options: "Tất cả ca", "Ca sáng", "Ca chiều", "Ca tối")
     * Dropdown 2: "Tất cả Role_Tag" (Options: "Tất cả Role_Tag", "Pha chế", "Thu ngân", "Phục vụ", "Quản lý ca")
     * Button "Xóa bộ lọc"
     * Right Button: Outline button with Excel icon: "Xuất Excel"
   - Dashboard Body Grid (2 Columns layout):
     * Left Grid Column (Width 60%):
       - Block 1: "Cần xử lý ngay" (TodoBlock):
         List of pending items:
         + "2 yêu cầu đổi ca đang chờ quản lý duyệt" (Action button: "Xem đổi ca" -> GD-06)
         + "1 đơn xin nghỉ phép chưa được xử lý" (Action button: "Xem đơn nghỉ" -> GD-09)
         + "2 ca trực mở chưa có nhân viên nhận" (Action button: "Xem ca mở" -> GD-07)
       - Block 2: "Lịch trực tuần này" (ScheduleWeekBlock): Compact 7-day mini grid showing shift coverage status.
       - Block 3: "Biểu đồ chi phí lương theo ngày" (CostChartBlock): UI placeholder frame showing daily wage bars.
     * Right Grid Column (Width 40%):
       - Block 4: "Cảnh báo ca thiếu người" (UnderstaffedBlock):
         Red alert badge: "Chiều Thứ 6 (12:00 - 17:00) thiếu 1 Pha chế" (Button: "Mở ca trực" -> GD-07)
       - Block 5: "Thống kê chấm công" (AttendanceChartBlock): Mini donut chart with values: "Đúng giờ: 38 ca", "Đi trễ: 2 ca", "Vắng: 1 ca".
       - Block 6: "Gợi ý từ Trợ lý AI" (AssistantPanel): Inline card with question chips: "Ai nghỉ tuần này?", "Ca nào đang thiếu người?".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT render duplicate top headers or multiple sidebars.
- Do NOT display breadcrumbs like "Home / Dashboard".
```

---

### GD-02: Ca & Ràng buộc – Cấu hình ca mẫu & Quy tắc
* **Screen ID:** `GD-02` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình cấu hình hệ thống (`/manager/shift-config`). Quản lý thiết lập ca làm việc mẫu, ràng buộc cứng/mềm cho thuật toán OR-Tools và các cài đặt chung.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-02] Shift Templates & Constraints Configuration (/manager/shift-config)
Desktop web application frame within standard Manager layout, displaying the Shift Configuration Page with 3 status navigation tabs ("Ca mẫu", "Ràng buộc xếp lịch", "Cấu hình chung"), shift templates table, filter tools, and action button "Thêm ca mẫu".

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Surface White #FFFFFF, Border #E2E8F0, Slate #475569, Background #F2F6FC
- Typography: "Be Vietnam Pro", Title 18px font-semibold, Body 14px.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Consistent Manager Portal navigation. Active sidebar item: "Ca & ràng buộc" under group "Lịch và ca".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Ca & ràng buộc". Right Action Button: Primary button with plus icon: "Thêm ca mẫu" (triggers modal).
   - Header Row 2: Subtitle "Ca làm việc mẫu, quy tắc xếp lịch tự động và cấu hình chung hệ thống."
   - Toolbar with 3 Top Tabs:
     * Tab 1 (Active): "Ca mẫu" (Badge count: "4")
     * Tab 2: "Ràng buộc xếp lịch"
     * Tab 3: "Cấu hình chung"
   - Sub-Toolbar for "Ca mẫu" tab:
     * Search Input (w-72): Placeholder "Tìm tên ca..."
     * Status Filter: "Tất cả trạng thái" (Options: "Tất cả trạng thái", "Đang áp dụng", "Tạm dừng")
     * Clear Filter Button: "Xóa bộ lọc"
   - Data Table (Shift Templates Table, overflow-x-auto, width 100%, table-fixed):
     * Columns & Widths:
       1. Cột "Mã ca" (15%): "SHIFT-SANG", "SHIFT-CHIEU", "SHIFT-TOI", "SHIFT-PEAK"
       2. Cột "Tên ca & Mô tả" (30%):
          - Row 1: "Ca sáng" (Phục vụ điểm tâm & chuẩn bị thiết bị)
          - Row 2: "Ca chiều" (Phục vụ giờ trưa & bàn giao nguyên liệu)
          - Row 3: "Ca tối" (Phục vụ đồ uống & dọn dẹp đóng cửa)
          - Row 4: "Ca cao điểm (Part-time)" (Tăng cường giờ đông khách)
       3. Cột "Khung giờ" (15%): "06:00 - 12:00 (6h)", "12:00 - 17:00 (5h)", "17:00 - 22:00 (5h)", "11:00 - 14:00 (3h)"
       4. Cột "Định biên chuẩn" (15%): "1 Phục vụ, 1 Pha chế", "1 Pha chế, 1 Thu ngân", "1 Phục vụ, 1 Trưởng ca", "1 Phục vụ, 1 Pha chế"
       5. Cột "Hệ số lương" (10%): "x1.0", "x1.0", "x1.2 (Ca đêm)", "x1.0"
       6. Cột "Trạng thái" (10%): StatusBadge green dot "Đang áp dụng"
       7. Cột "Thao tác" (5%): Action menu button "⋮" (Options: "Chỉnh sửa", "Tạm dừng", "Xóa")
   - Pagination Footer: "Hiển thị 1–4 trong 4 ca mẫu", pagination buttons "«", "‹", "1", "›", "»", select "7 / trang".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT display breadcrumb navigation.
- No second header bar.
```

---

### GD-03: Khả năng làm việc – Ma trận khảo sát lịch rảnh tuần
* **Screen ID:** `GD-03` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình khảo sát lịch đăng ký của nhân viên (`/manager/availability`). Quản lý đối chiếu nhân sự nào đã đăng ký rảnh/bận trước khi kích hoạt thuật toán xếp lịch tự động tuần tới.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-03] Staff Weekly Availability Matrix (/manager/availability)
Desktop web view showing employee weekly availability surveying board across 7 days (Monday through Sunday) for Morning, Afternoon, and Evening shifts. Features week navigation controls, filter tabs, and a full availability matrix.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Green Available #10B981 (bg #ECFDF5), Gray Busy #94A3B8 (bg #F1F5F9), Unregistered Amber #F59E0B.
- Table: Bordered matrix table with sticky column for employee names.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Khả năng làm việc".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Khả năng làm việc".
     Right Action Controls: Week selector component with calendar icon:
     [Button "‹ Tuần trước"] Text: "Tuần sau: 27/10/2026 – 02/11/2026" [Button "Tuần sau ›"]
   - Header Row 2: Subtitle "Tổng hợp thời gian rảnh/bận do nhân viên đăng ký cho tuần tới. Dữ liệu làm căn cứ xếp ca tự động."
   - Status Tabs Bar:
     * Tab "Tất cả" (Count: "7")
     * Tab "Đã đăng ký" (Count: "6")
     * Tab "Chưa đăng ký" (Count: "1")
   - Filter Toolbar:
     * Search Input (w-72): Placeholder "Tìm tên hoặc mã nhân viên..."
     * Filter Dropdown: "Tất cả vị trí" (Options: "Tất cả vị trí", "Pha chế", "Thu ngân", "Phục vụ", "Quản lý ca")
     * Button: "Xóa bộ lọc"
   - Weekly Availability Matrix Table (Width 100%, table-fixed, rounded-xl border border-slate-200):
     * Header Row 1: Column "Nhân viên" (16%), then 7 day headers (12% each): "Thứ 2 (27/10)", "Thứ 3 (28/10)", "Thứ 4 (29/10)", "Thứ 5 (30/10)", "Thứ 6 (31/10)", "Thứ 7 (01/11)", "Chủ nhật (02/11)".
     * Header Row 2 (Sub-shifts for each day): "S" (Sáng), "C" (Chiều), "T" (Tối).
     * Data Rows:
       - Row 1: Avatar "NA", "Nguyễn Văn An" (Shift_Leader) -> Green cells with checkmark on registered available shifts, gray on busy shifts.
       - Row 2: Avatar "TB", "Trần Thị Bích" (Barista) -> Detailed availability indicators.
       - Row 3: Avatar "LC", "Lê Minh Cường" (Cashier) -> Detailed availability indicators.
       - Row 4: Avatar "PD", "Phạm Thị Dung" (Barista, status: Nghỉ phép) -> Yellow indicator bar across all days: "Chưa đăng ký lịch rảnh".
       - Row 5: Avatar "HN", "Hoàng Văn Nam" (Cashier) -> Detailed availability indicators.
       - Row 6: Avatar "VT", "Vũ Minh Thảo" (Barista) -> Detailed availability indicators.
       - Row 7: Avatar "DY", "Đặng Hoàng Yến" (Waiter) -> Detailed availability indicators.
   - Legend Bar (Bottom):
     Green dot: "Rảnh (sẵn sàng làm)" | Gray dot: "Bận" | Amber dot: "Chưa nộp lịch đăng ký"
   - Pagination: "Hiển thị 1–7 trong 7 nhân viên".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT display employee "Đỗ Kim Ngân" (NV006) because she is resigned ("Nghỉ việc").
- Do NOT add extraneous buttons or unverified breadcrumb controls.
```

---

### GD-04: Tạo lịch tự động – Cấu hình định biên & OR-Tools
* **Screen ID:** `GD-04` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình cốt lõi của tính năng xếp lịch tự động (`/manager/schedule`). Quản lý nhập định biên nhu cầu theo từng ca trong tuần, cấu hình ràng buộc và kích hoạt thuật toán Google OR-Tools.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-04] Auto Schedule Generator & Shift Demands (/manager/schedule)
Desktop web application frame showing the automatic schedule generation workbench. Contains underline navigation tabs ("Tạo lịch tự động" | "Lịch đã tạo"), week navigator, top action buttons ("Xem lịch chi tiết", "Xác nhận lịch", "Tạo lịch tự động"), shift demand cards, constraint toggles, and the tentative weekly assignment matrix.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Primary Hover #3452A8, Emerald Confirmed #10B981, Amber Understaffed #F59E0B, Surface White #FFFFFF, Border #E2E8F0.
- Typography: "Be Vietnam Pro", Tabular numbers for shift counts and ratios.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Lịch làm việc" -> child "Tạo lịch tự động".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Tạo lịch tự động".
     Right Action Buttons:
     * Week Selector: "20/10/2026 – 26/10/2026" with prev/next chevron buttons.
     * Button 1 (Outline): CalendarDays icon + "Xem lịch chi tiết" (links to GD-05).
     * Button 2 (Outline, Emerald text): FileCheck2 icon + "Xác nhận lịch".
     * Button 3 (Primary, Solid Blue #3F62C4): Cpu icon + "Tạo lịch tự động" (triggers OR-Tools modal GD-23).
   - Header Row 2: Subtitle "Cấu hình nhu cầu nhân sự và các ràng buộc để hệ thống sử dụng OR-Tools tạo lịch làm việc tự động (bản mô phỏng)."
   - Header Row 3 (Tabs): Active tab "Tạo lịch tự động" (blue border-b-2) | Inactive tab "Lịch đã tạo" (links to GD-05).
   - Schedule Preparation Progress Bar:
     Card showing 3 readiness criteria:
     * Criterion 1: "Lịch rảnh nhân viên: 6/7 đã nộp" (Green check icon)
     * Criterion 2: "Định biên 21 ca: Đã cấu hình" (Green check icon)
     * Criterion 3: "Ràng buộc pháp lý: 3/3 kích hoạt" (Green check icon)
   - Section 1: "Nhu cầu nhân sự theo ca (Shift Demands)" (ShiftDemandCard):
     7-day selector cards ("T2", "T3", "T4", "T5", "T6", "T7", "CN").
     Shift rows for selected day:
     * "Ca sáng (06:00 - 12:00)": Barista: 1, Thu ngân: 0, Phục vụ: 1.
     * "Ca chiều (12:00 - 17:00)": Barista: 1, Thu ngân: 1, Phục vụ: 0.
     * "Ca tối (17:00 - 22:00)": Barista: 0, Thu ngân: 0, Phục vụ: 1, Quản lý ca: 1.
     * Action button: "Sao chép nhu cầu sang ngày cuối tuần".
   - Section 2: "Ràng buộc xếp ca (Schedule Constraints)" (ScheduleConstraintsCard):
     Toggle switches list:
     * Toggle 1 (Active): "Tối đa 1 ca / ngày / nhân viên" (Ràng buộc cứng)
     * Toggle 2 (Active): "Nghỉ giữa 2 ca tối thiểu 12 giờ" (Ràng buộc cứng)
     * Toggle 3 (Active): "Ưu tiên nhân viên toàn thời gian đủ 40 giờ/tuần" (Ràng buộc mềm)
     * Toggle 4 (Active): "Cân bằng số ca tối giữa các nhân viên" (Ràng buộc mềm)
   - Section 3: "Ma trận phân ca mô phỏng (Draft Schedule Matrix)" (ScheduleMatrixCard):
     Grid displaying 21 shift cells (7 days x 3 shifts).
     Each cell shows: Shift title, assigned employee avatar tags with role badge, and status pill:
     - Filled cell: Green pill "Đủ người (2/2)"
     - Understaffed cell: Amber pill "Thiếu 1 Pha chế" (clickable, opens shift edit modal).

STRICT NEGATIVE CONSTRAINTS:
- Do NOT allow auto-scheduling for resigned employees ("Nghỉ việc").
- No breadcrumb path string.
```

---

### GD-05: Lịch đã tạo – Phân công ca trực theo tuần (Lưới & Danh sách)
* **Screen ID:** `GD-05` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình xem và quản lý các ca trực đã phân công theo tuần (`/manager/schedule/created`). Cho phép chuyển đổi giữa dạng Lưới trực quan (Grid View) và dạng Bảng danh sách chi tiết (List View), hỗ trợ xuất Excel.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-05] Created Schedule Management - Grid & List View (/manager/schedule/created)
Desktop web application frame showing the Created Schedule View. Contains the top underline tabs ("Tạo lịch tự động" | "Lịch đã tạo" - active), period selector, StatStrip summary, View Switcher (Dạng lưới / Dạng danh sách), filters, search input, and the complete weekly schedule grid.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Emerald Status #10B981, Amber Alert #F59E0B, Card White #FFFFFF, Border #E2E8F0.
- Layout: 7-day grid calendar layout with time-block cards.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Lịch làm việc" -> child "Lịch đã tạo".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Lịch đã tạo".
     Right Action Controls:
     * Period dropdown: "Tuần này | 20/10/2026 – 26/10/2026"
     * Button (Primary): Cpu icon + "Tạo lịch tự động" (links back to GD-04).
   - Header Row 2: Subtitle "Xem và quản lý các ca trực đã được phân công theo tuần."
   - Header Row 3 (Tabs): Inactive tab "Tạo lịch tự động" | Active tab "Lịch đã tạo" (blue border-b-2).
   - StatStrip (4 Metrics):
     * Metric 1: Calendar icon | "Tổng ca trực" | "21 ca"
     * Metric 2: CheckCircle2 icon | "Đã phân công" | "19 ca"
     * Metric 3: AlertTriangle icon | "Ca còn thiếu" | "2 ca" (Amber dot)
     * Metric 4: Users icon | "Nhân viên tham gia" | "7 người"
   - Toolbar:
     * Left: View Switcher Segmented Control:
       [Button (Active with icon): "Dạng lưới"] [Button: "Dạng danh sách"]
     * Middle Filters:
       - Search Input (w-72): Placeholder "Tìm nhân viên..."
       - Role Filter Dropdown: "Tất cả vị trí" (Options: "Tất cả vị trí", "Pha chế", "Thu ngân", "Phục vụ", "Quản lý ca")
       - Shift Filter Dropdown: "Tất cả ca" (Options: "Tất cả ca", "Ca sáng", "Ca chiều", "Ca tối")
       - Button "Xóa bộ lọc"
     * Right Action: ExportButton with Excel icon: "Xuất Excel"
   - Schedule Grid View (CreatedScheduleGridView, 7 columns for 7 days):
     * Day Headers: "Thứ 2 (20/10)", "Thứ 3 (21/10)", "Thứ 4 (22/10)", "Thứ 5 (23/10)", "Thứ 6 (24/10)", "Thứ 7 (25/10)", "Chủ nhật (26/10)".
     * Inside Each Day Column: 3 shift cards:
       1. "Ca sáng (06:00 - 12:00)": Assigned employees with avatar pills (e.g. "Trần Thị Bích", "Nguyễn Văn An"), Status: "2/2 nhân viên".
       2. "Ca chiều (12:00 - 17:00)": Understaffed alert on Friday: "1/2 nhân viên (Thiếu 1 Pha chế)" in amber badge, button "Điều chỉnh / Mở ca".
       3. "Ca tối (17:00 - 22:00)": Assigned employees tags.
   - Clickable cell action: Clicking any shift card opens GD-23 Assignment Modal.

STRICT NEGATIVE CONSTRAINTS:
- Do NOT display fake feedback reviews or rating stars.
- No secondary header bar.
```

---

### GD-06: Đổi ca – Quản lý đối soát & Phê duyệt yêu cầu đổi ca
* **Screen ID:** `GD-06` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình duyệt đổi ca của Quản lý (`/manager/shift-exchange`). Quản lý xem xét, duyệt hoặc từ chối các yêu cầu hoán đổi ca (Swap) hoặc nhờ làm thay (Cover) giữa các nhân sự.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-06] Manager Shift Exchange Review (/manager/shift-exchange)
Desktop web application frame showing the Shift Exchange Approval Page. Features status tabs ("Tất cả", "Chờ quản lý duyệt", "Chờ đối tác đồng ý", "Đã duyệt", "Đã từ chối"), role filters, search input, and an audit table with action buttons for approving and rejecting.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Green #10B981, Red Danger #D64550, Amber Pending #F59E0B, Background #F2F6FC.
- Table: Overflow-x-auto, fixed width columns totaling 100%.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Đổi ca" under group "Lịch và ca".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Đổi ca".
   - Header Row 2: Subtitle "Xem xét, duyệt hoặc từ chối các yêu cầu đổi ca giữa các nhân viên."
   - Status Tabs Bar:
     * Tab 1: "Tất cả" (Count: "5")
     * Tab 2 (Active): "Chờ quản lý duyệt" (Count: "3")
     * Tab 3: "Chờ đối tác đồng ý" (Count: "1")
     * Tab 4: "Đã duyệt" (Count: "1")
     * Tab 5: "Đã từ chối" (Count: "0")
   - Toolbar:
     * Search Input (w-72): Placeholder "Tìm tên nhân viên, mã yêu cầu..."
     * Filter Dropdown: "Tất cả vị trí" (Options: "Tất cả vị trí", "Pha chế", "Thu ngân", "Phục vụ", "Quản lý ca")
     * Button: "Xóa bộ lọc"
   - Exchange Table (ExchangeTable, width 100%, table-fixed, rounded-xl border border-slate-200):
     * Columns & Widths:
       1. Cột "Mã đơn" (12%): "EX-2026-005", "EX-2026-006", "EX-2026-001"
       2. Cột "Người gửi & Ca làm" (24%):
          - Row 1: Avatar "LC", "Lê Minh Cường" (Thu ngân) | "Ca chiều (12:00 - 17:00), Thứ 6, 24/10"
          - Row 2: Avatar "TB", "Trần Thị Bích" (Pha chế) | "Ca chiều (12:00 - 17:00), Thứ 6, 24/10"
       3. Cột "Người nhận & Ca làm" (24%):
          - Row 1: Avatar "HN", "Hoàng Văn Nam" (Thu ngân) | "Ca sáng (06:00 - 12:00), Thứ 7, 25/10"
          - Row 2: Avatar "PD", "Phạm Thị Dung" (Pha chế, status: Nghỉ phép) | "Nhờ làm thay (không đổi ca)"
       4. Cột "Hình thức" (12%): Pill "Hoán đổi ca" (Swap) or Pill "Nhờ làm thay" (Cover)
       5. Cột "Lý do" (14%): "Đổi ca chiều Thứ 6 lấy ca sáng Thứ 7 để xử lý việc riêng.", "Nhờ trực thay do bận việc gia đình."
       6. Cột "Trạng thái" (14%): Amber pill "Chờ quản lý duyệt"
       7. Cột "Thao tác" (10%):
          Two compact action buttons:
          - Button 1 (Emerald #10B981 text): "Duyệt" (Triggers direct approval)
          - Button 2 (Red #D64550 text): "Từ chối" (Triggers reject reason modal)
   - Pagination: "Hiển thị 1–3 trong 3 yêu cầu", pageSize "7 / trang".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT approve requests if employee is in "Nghỉ việc" status.
- No duplicate topbar.
```

---

### GD-07: Ca trực mở – Quản lý ca trống & Đăng ký tăng cường
* **Screen ID:** `GD-07` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình quản lý ca trống (`/manager/open-shifts`). Quản lý đăng tin mở ca còn thiếu người kèm tiền thưởng tăng cường để nhân sự tự do đăng ký nhận ca.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-07] Open Shifts Management (/manager/open-shifts)
Desktop web application frame showing the Open Shifts Management Board. Features the page header with open shifts counter badge, top action button "Mở ca trực", status tabs, filter dropdowns, and an open shifts table detailing needed roles, bonus amounts, and claims.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Green #10B981, Amber #F59E0B, Gray #64748B, Surface White #FFFFFF.
- Currency Format: Vietnamese Dong with dot separator (e.g., "50.000 ₫").

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Ca trực mở".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Ca trực mở". Right Action Button: Primary button with plus icon: "Mở ca trực".
   - Header Row 2: Subtitle: "Các ca còn thiếu nhân viên. Nhân viên đủ điều kiện tự nhận ca, ai nhận trước được trước. Đang mở: 3 ca" (3 highlighted in bold).
   - Status Tabs Bar:
     * Tab 1 (Active): "Tất cả" (Count: "5")
     * Tab 2: "Đang mở" (Count: "3")
     * Tab 3: "Đã đủ người" (Count: "1")
     * Tab 4: "Đã hủy" (Count: "1")
   - Toolbar:
     * Search Input (w-72): Placeholder "Tìm ca, ngày hoặc tên nhân viên..."
     * Filter Dropdown: "Tất cả Role_Tag" (Options: "Tất cả Role_Tag", "Barista", "Cashier", "Waiter", "Shift_Leader")
     * Button: "Xóa bộ lọc"
   - Open Shifts Table (Width 100%, table-fixed, rounded-xl border border-slate-200):
     * Columns & Widths:
       1. Cột "Mã ca & Ngày" (18%):
          - Row 1: "OS001" | "Thứ 6, 24/10/2026"
          - Row 2: "OS002" | "Thứ 3, 21/10/2026"
          - Row 3: "OS003" | "Thứ 6, 24/10/2026"
       2. Cột "Khung giờ" (15%): "12:00 - 17:00 (Ca chiều)", "12:00 - 17:00 (Ca chiều)", "17:00 - 22:00 (Ca tối)"
       3. Cột "Vị trí cần" (14%): Blue badge "Barista", Blue badge "Barista", Blue badge "Shift_Leader"
       4. Cột "Nhu cầu" (12%): "0/1 người", "0/1 người", "0/1 người"
       5. Cột "Thưởng ca" (13%): Green font-semibold "+50.000 ₫", "+30.000 ₫", "+40.000 ₫"
       6. Cột "Ghi chú" (16%): "Thay thế vị trí Pha chế do nhân viên nghỉ phép", "Bổ sung vị trí pha chế", "Cần trưởng ca hỗ trợ đóng cửa"
       7. Cột "Trạng thái" (8%): StatusBadge green dot "Đang mở"
       8. Cột "Thao tác" (4%): Action menu "⋮" (Options: "Xem chi tiết", "Chỉnh sửa", "Hủy ca")
   - Pagination: "Hiển thị 1–5 trong 5 ca trực mở".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT display claims from locked payroll periods.
- No breadcrumb trail.
```

---

### GD-08: Quản lý nhân viên – Hồ sơ nhân sự, hợp đồng & mức lương
* **Screen ID:** `GD-08` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình hồ sơ nhân sự (`/manager/employees`). Quản lý xem danh sách nhân viên, thêm mới, sửa hợp đồng, cập nhật mức lương giờ và cấp tài khoản đăng nhập.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-08] Employee Directory & Profile Management (/manager/employees)
Desktop web application frame displaying the Employee Directory Page. Includes header with "Thêm nhân viên" button, status tabs ("Tất cả", "Đang làm việc", "Nghỉ phép", "Nghỉ việc"), contract filter, role filter, search bar, and the comprehensive staff table.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Emerald #10B981, Amber #F59E0B, Rose #D64550, Surface White #FFFFFF.
- Typography: "Be Vietnam Pro", Tabular numbers for phone and wages.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Quản lý nhân viên" under group "Nhân sự".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Quản lý nhân viên". Right Action Button: Primary button with plus icon: "Thêm nhân viên".
   - Header Row 2: Subtitle "Danh sách nhân viên và thông tin phục vụ việc xếp lịch."
   - Status Tabs Bar:
     * Tab 1 (Active): "Tất cả" (Count: "8")
     * Tab 2: "Đang làm việc" (Count: "6")
     * Tab 3: "Nghỉ phép" (Count: "1")
     * Tab 4: "Nghỉ việc" (Count: "1")
   - Toolbar:
     * Search Input (w-72): Placeholder "Tìm tên hoặc mã nhân viên..."
     * Filter Dropdown 1: "Tất cả vị trí" (Options: "Tất cả vị trí", "Pha chế", "Thu ngân", "Phục vụ", "Quản lý ca")
     * Filter Dropdown 2: "Tất cả hợp đồng" (Options: "Tất cả hợp đồng", "Toàn thời gian", "Bán thời gian")
     * Button: "Xóa bộ lọc"
   - Employee Table (EmployeeTable, width 100%, table-fixed, rounded-xl border border-slate-200):
     * Columns & Widths:
       1. Cột "Nhân viên" (25%): Avatar circle with initials + Full Name + Employee Code below:
          - Row 1: Avatar "NA", "Nguyễn Văn An" | "NV001 • nguyenvanan@gmail.com"
          - Row 2: Avatar "TB", "Trần Thị Bích" | "NV002 • tranthibich@gmail.com"
          - Row 3: Avatar "LC", "Lê Minh Cường" | "NV003 • leminhcuong@gmail.com"
          - Row 4: Avatar "PD", "Phạm Thị Dung" | "NV004 • phamthidung@gmail.com"
          - Row 5: Avatar "HN", "Hoàng Văn Nam" | "NV005 • hoangvannam@gmail.com"
          - Row 6: Avatar "DN", "Đỗ Kim Ngân" | "NV006 • dokimngan@gmail.com"
          - Row 7: Avatar "VT", "Vũ Minh Thảo" | "NV007 • vuminhthao@gmail.com"
       2. Cột "Vị trí (Role_Tag)" (16%):
          Pills: "Quản lý ca", "Pha chế", "Thu ngân", "Pha chế", "Thu ngân", "Phục vụ", "Pha chế"
       3. Cột "Hợp đồng" (15%): "Toàn thời gian", "Toàn thời gian", "Bán thời gian", "Bán thời gian", "Bán thời gian", "Bán thời gian", "Bán thời gian"
       4. Cột "Số điện thoại" (14%): "0905123456", "0905123457", "0905123458", "0905123459", "0905123460", "0905123461", "0905123462"
       5. Cột "Lương / giờ" (14%): "35.000 đ/h", "30.000 đ/h", "28.000 đ/h", "30.000 đ/h", "28.000 đ/h", "25.000 đ/h", "30.000 đ/h"
       6. Cột "Trạng thái" (12%):
          - Row 1-3, 5, 7: StatusBadge green dot "Đang làm việc"
          - Row 4: StatusBadge amber dot "Nghỉ phép"
          - Row 6: StatusBadge rose dot "Nghỉ việc"
       7. Cột "Thao tác" (4%): Action button "⋮" (Options: "Xem chi tiết", "Chỉnh sửa", "Cấp tài khoản", "Xóa nhân viên")
   - Pagination: "Hiển thị 1–7 trong 8 nhân viên", select "7 / trang".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT display rating stars or customer reviews.
```

---

### GD-09: Đơn xin nghỉ – Phê duyệt đơn nghỉ phép & AI gợi ý thay thế
* **Screen ID:** `GD-09` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình duyệt đơn nghỉ phép (`/manager/leave`). Quản lý xem các đơn xin nghỉ đột xuất/kế hoạch và sử dụng AI gợi ý nhân sự thay thế phù hợp nhất để bù đắp ca trống.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-09] Leave Requests & AI Replacement Suggestions (/manager/leave)
Desktop web application frame showing the Leave Requests Management View. Displays top actions ("Đặt lại dữ liệu mẫu", "Tạo đơn xin nghỉ"), AI coverage rate banner, status tabs, role filters, and a leave requests table.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Green #10B981, Amber #F59E0B, Red #D64550, Sparkle Purple #8B5CF6, Surface White #FFFFFF.
- Typography: "Be Vietnam Pro", Tabular numbers for dates and percentages.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Đơn xin nghỉ" under group "Nhân sự".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Đơn xin nghỉ".
     Right Action Buttons:
     * Button 1 (Outline): "Đặt lại dữ liệu mẫu"
     * Button 2 (Primary): Plus icon + "Tạo đơn xin nghỉ"
   - Header Row 2: Subtitle: "Theo dõi, phê duyệt đơn xin nghỉ và để AI gợi ý người thay ca phù hợp. Tỷ lệ lấp ca bằng AI: 80%" (80% bold).
   - Status Tabs Bar:
     * Tab 1: "Tất cả" (Count: "6")
     * Tab 2 (Active): "Chờ duyệt" (Count: "2")
     * Tab 3: "Đã duyệt" (Count: "3")
     * Tab 4: "Từ chối" (Count: "1")
   - Toolbar:
     * Search Input (w-72): Placeholder "Tìm mã đơn, tên nhân viên..."
     * Filter Dropdown: "Tất cả vị trí" (Options: "Tất cả vị trí", "Pha chế", "Thu ngân", "Phục vụ", "Quản lý ca")
     * Button: "Xóa bộ lọc"
   - Leave Requests Table (LeaveTable, width 100%, table-fixed, rounded-xl border border-slate-200):
     * Columns & Widths:
       1. Cột "Mã đơn" (12%): "LV-2026-001", "LV-2026-002", "LV-2026-003", "LV-2026-004"
       2. Cột "Nhân viên" (18%):
          - Row 1: Avatar "TB", "Trần Thị Bích" (Pha chế)
          - Row 2: Avatar "LC", "Lê Minh Cường" (Thu ngân)
       3. Cột "Ca xin nghỉ & Ngày" (20%):
          - Row 1: "Ca sáng (06:00 - 12:00)", "Thứ 7, 25/10/2026"
          - Row 2: "Ca tối (17:00 - 22:00)", "Chủ nhật, 26/10/2026"
       4. Cột "Loại nghỉ" (12%): Badge "Nghỉ ốm đột xuất", Badge "Nghỉ có kế hoạch"
       5. Cột "Lý do" (18%): "Bị cảm sốt cần nghỉ ngơi và đi khám bệnh.", "Bận việc gia đình đột xuất."
       6. Cột "Gợi ý AI" (10%): Sparkles icon + "Vũ Minh Thảo (95%)", Sparkles icon + "Hoàng Văn Nam (90%)"
       7. Cột "Trạng thái" (10%): StatusBadge amber dot "Chờ duyệt"
       8. Cột "Thao tác" (10%):
          Action buttons:
          - Button "Chi tiết & Gợi ý" (Opens GD-26)
          - Button (Green): "Duyệt"
          - Button (Red): "Từ chối"
   - Pagination: "Hiển thị 1–4 trong 6 đơn xin nghỉ".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT approve leave requests for shifts in locked payroll periods.
```

---

### GD-10: Chấm công – Đối soát giờ vào/ra thực tế & Điểm danh
* **Screen ID:** `GD-10` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình đối soát chấm công (`/manager/attendance`). Quản lý so khớp kế hoạch xếp ca với giờ check-in / check-out thực tế, phát hiện ca đi trễ, vắng mặt hoặc quên checkout, và thực hiện điều chỉnh giờ.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-10] Attendance Audit & Time Reconciliation (/manager/attendance)
Desktop web application frame displaying the Attendance Audit Page. Features underline navigation tabs ("Chấm công" - active | "Giờ làm"), period picker, StatStrip with punch status counts, View Switcher (List vs Grid), attendance audit table, and row click handler to adjust time records.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Green On-Time #10B981, Amber Late #F59E0B, Rose Absent #D64550, Orange Missing-Out #F97316.
- Typography: "Be Vietnam Pro", Tabular numbers for times and deviations.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Chấm công & Giờ làm" -> child "Chấm công".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Chấm công".
     Right Action Controls:
     * Period dropdown: "Tuần này | 20/10/2026 – 26/10/2026"
     * ExportButton with Excel icon: "Xuất Excel"
   - Header Row 2: Subtitle "Đối soát giờ vào, giờ ra thực tế với kế hoạch phân ca. Bấm vào dòng để điều chỉnh khi có sai sót."
   - Header Row 3 (Tabs): Active tab "Chấm công" (blue border-b-2) | Inactive tab "Giờ làm" (links to GD-11).
   - StatStrip (5 KPI Metrics):
     * Metric 1: CheckCircle2 icon | "Đúng giờ" | "35 ca" (Green dot)
     * Metric 2: Clock icon | "Đi trễ" | "2 ca" (Amber dot)
     * Metric 3: UserX icon | "Vắng mặt" | "1 ca" (Rose dot)
     * Metric 4: AlertTriangle icon | "Quên chấm ra" | "1 ca" (Orange dot)
     * Metric 5: ClipboardCheck icon | "Tổng ca hoàn thành" | "39 ca"
   - Toolbar:
     * Left View Switcher: [Button (Active): "Dạng danh sách"] [Button: "Dạng lưới chấm công"]
     * Middle Filters:
       - Search Input (w-72): Placeholder "Tìm nhân viên..."
       - Status Filter Dropdown: "Tất cả trạng thái" (Options: "Tất cả trạng thái", "Đúng giờ", "Đi trễ", "Vắng mặt", "Quên chấm ra", "Đang diễn ra", "Sắp tới")
       - Role Filter Dropdown: "Tất cả vị trí" (Options: "Tất cả vị trí", "Pha chế", "Thu ngân", "Phục vụ", "Quản lý ca")
       - Button: "Xóa bộ lọc"
   - Attendance Table (Table clean variant, width 100%, table-fixed, rounded-xl border border-slate-200):
     * Columns & Widths:
       1. Cột "Ngày & Ca làm việc" (20%):
          - Row 1: "Thứ 2, 20/10/2026" | "Ca sáng (06:00 - 12:00)"
          - Row 2: "Thứ 2, 20/10/2026" | "Ca sáng (06:00 - 12:00)"
          - Row 3: "Thứ 3, 21/10/2026" | "Ca chiều (12:00 - 17:00)"
          - Row 4: "Thứ 4, 22/10/2026" | "Ca tối (17:00 - 22:00)"
       2. Cột "Nhân viên" (20%):
          - Row 1: Avatar "NA", "Nguyễn Văn An" (Shift_Leader)
          - Row 2: Avatar "TB", "Trần Thị Bích" (Barista)
          - Row 3: Avatar "LC", "Lê Minh Cường" (Cashier)
          - Row 4: Avatar "DY", "Đặng Hoàng Yến" (Waiter)
       3. Cột "Kế hoạch" (14%): "06:00 - 12:00 (6.0h)", "06:00 - 12:00 (6.0h)", "12:00 - 17:00 (5.0h)", "17:00 - 22:00 (5.0h)"
       4. Cột "Vào thực tế" (12%): "05:58", "06:15" (Late), "11:55", "17:02"
       5. Cột "Ra thực tế" (12%): "12:02", "12:00", "— (Chưa ra)", "22:05"
       6. Cột "Chênh lệch" (10%): "+4 phút", "-15 phút", "—", "+5 phút"
       7. Cột "Trạng thái" (12%):
          - Row 1: StatusBadge green dot "Đúng giờ"
          - Row 2: StatusBadge amber dot "Đi trễ (15p)"
          - Row 3: StatusBadge orange dot "Quên chấm ra"
          - Row 4: StatusBadge green dot "Đúng giờ"
   - Row Click Action Note: "Nhấp chuột vào bất kỳ dòng nào để mở hộp thoại điều chỉnh chấm công." (Opens GD-24).
   - Pagination: "Hiển thị 1–7 trong 39 ca", select "7 / trang".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT mark future shifts as "Vắng mặt" (Absent); they must show as "Sắp tới" (Upcoming).
```

---

### GD-11: Giờ làm – Tổng hợp giờ làm việc thực tế & Tỷ lệ kế hoạch
* **Screen ID:** `GD-11` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình tổng hợp giờ làm việc (`/manager/work-hours`). Quản lý theo dõi tổng số giờ đã phân công theo kế hoạch, giờ làm thực tế, số giờ làm việc ca đêm và tỷ lệ hoàn thành của từng nhân viên.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-11] Work Hours Aggregation & Analysis (/manager/work-hours)
Desktop web application frame showing the Work Hours Summary Page. Features underline navigation tabs ("Chấm công" | "Giờ làm" - active), period selector, StatStrip, employee and role filters, and the comprehensive staff work hours table with clickable rows to view employee shift breakdowns.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Emerald #10B981, Indigo Night #6366F1, Surface White #FFFFFF, Border #E2E8F0.
- Typography: "Be Vietnam Pro", Tabular numbers for hours and percentages.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Chấm công & Giờ làm" -> child "Giờ làm".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Giờ làm".
     Right Action Controls:
     * Period dropdown: "Tuần này | 20/10/2026 – 26/10/2026"
     * ExportButton with Excel icon: "Xuất Excel"
   - Header Row 2: Subtitle "Tổng hợp giờ làm theo kế hoạch và thực tế. Nhấp vào nhân viên để xem chi tiết từng ca."
   - Header Row 3 (Tabs): Inactive tab "Chấm công" (links to GD-10) | Active tab "Giờ làm" (blue border-b-2).
   - StatStrip (4 Summary Metrics):
     * Metric 1: Clock icon | "Tổng giờ kế hoạch" | "220.0 h"
     * Metric 2: CheckCircle2 icon | "Tổng giờ thực tế" | "214.5 h"
     * Metric 3: TrendingUp icon | "Tỷ lệ hoàn thành" | "97.5%" (Green text)
     * Metric 4: Moon icon | "Giờ ca đêm" | "45.0 h" (Hệ số x1.2)
   - Toolbar:
     * Search Input (w-72): Placeholder "Tìm nhân viên..."
     * Filter Dropdown 1: "Tất cả vị trí" (Options: "Tất cả vị trí", "Pha chế", "Thu ngân", "Phục vụ", "Quản lý ca")
     * Filter Dropdown 2: "Tất cả nhân viên"
     * Button: "Xóa bộ lọc"
   - Work Hours Table (Table clean variant, width 100%, table-fixed, rounded-xl border border-slate-200):
     * Columns & Widths:
       1. Cột "Nhân viên" (22%): Avatar + Tên + Mã NV:
          - Row 1: "Nguyễn Văn An" (NV001)
          - Row 2: "Trần Thị Bích" (NV002)
          - Row 3: "Lê Minh Cường" (NV003)
          - Row 4: "Hoàng Văn Nam" (NV005)
          - Row 5: "Vũ Minh Thảo" (NV007)
          - Row 6: "Đặng Hoàng Yến" (NV008)
       2. Cột "Vị trí" (14%): "Quản lý ca", "Pha chế", "Thu ngân", "Thu ngân", "Pha chế", "Phục vụ"
       3. Cột "Hợp đồng" (14%): "Toàn thời gian", "Toàn thời gian", "Bán thời gian", "Bán thời gian", "Bán thời gian", "Bán thời gian"
       4. Cột "Giờ kế hoạch" (12%): "40.0 h", "38.0 h", "25.0 h", "24.0 h", "26.0 h", "25.0 h"
       5. Cột "Giờ thực tế" (12%): "40.5 h", "36.5 h", "25.0 h", "23.5 h", "26.0 h", "24.5 h"
       6. Cột "Chênh lệch" (10%): "+0.5 h", "-1.5 h", "0.0 h", "-0.5 h", "0.0 h", "-0.5 h"
       7. Cột "Tỷ lệ" (8%): Green pill "101%", Amber pill "96%", Green pill "100%", Green pill "98%", Green pill "100%", Green pill "98%"
       8. Cột "Giờ đêm" (8%): "10.0 h", "5.0 h", "0.0 h", "0.0 h", "5.0 h", "10.0 h"
   - Table Footer Row (Bold summary totals):
     "Tổng cộng" | "" | "" | "220.0 h" | "214.5 h" | "-5.5 h" | "97.5%" | "45.0 h"
   - Pagination: "Hiển thị 1–6 trong 6 nhân viên".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT include resigned staff ("Đỗ Kim Ngân").
```

---

### GD-12: Tính lương – Bảng lương tổng hợp & Chốt kỳ lương
* **Screen ID:** `GD-12` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình bảng lương (`/manager/payroll`). Quản lý đối chiếu tiền lương theo giờ thực tế, tính phụ cấp ca đêm và thưởng ca mở, thực hiện chốt sổ kỳ lương bất biến (Payroll Lock) và xuất bảng lương Excel.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-12] Payroll Summary & Immutable Period Locking (/manager/payroll)
Desktop web application frame showing the Manager Payroll Page. Features period selector, Action buttons ("Chốt kỳ lương", "Xuất Excel"), 5 StatStrip payroll KPI cards, payroll warning alerts, and a comprehensive payroll calculation table with payslip inspection modal triggers.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Emerald Paid #10B981, Amber Locked #F59E0B, Rose Warning #D64550, Surface White #FFFFFF.
- Currency Format: Vietnamese Dong (₫).

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Tính lương" under group "Tài chính".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Tính lương".
     Right Action Buttons:
     * Period dropdown: "Tháng này | Tháng 10/2026"
     * Button 1 (Amber outline with lock icon): "Chốt kỳ lương" (or "Mở khóa kỳ lương" if locked)
     * Button 2 (Outline with Excel icon): "Xuất Excel"
   - Header Row 2: Subtitle "Bảng tính lương dựa trên giờ làm thực tế đối soát từ chấm công, phụ cấp ca đêm và thưởng ca mở."
   - StatStrip (5 Financial KPI Cards):
     * Card 1: Wallet icon | "Tổng lương dự kiến" | "68.420.000 ₫"
     * Card 2: Clock icon | "Tổng giờ làm việc" | "860.0 h"
     * Card 3: Banknote icon | "Lương cơ bản" | "62.500.000 ₫"
     * Card 4: Gift icon | "Phụ cấp & Thưởng" | "5.920.000 ₫" (Đêm + Ca mở)
     * Card 5: Users icon | "Nhân viên được tính" | "6 người"
   - Conditional Period Status Banner:
     When period is locked: Soft amber banner with lock icon: "Kỳ lương Tháng 10/2026 đã được chốt sổ. Toàn bộ số liệu đã được lưu trữ bất biến và không thể sửa đổi chấm công."
   - Toolbar:
     * Search Input (w-72): Placeholder "Tìm nhân viên..."
     * Contract Filter Dropdown: "Tất cả hợp đồng" (Options: "Tất cả hợp đồng", "Toàn thời gian", "Bán thời gian")
     * Button: "Xóa bộ lọc"
   - Payroll Table (Width 100%, table-fixed, rounded-xl border border-slate-200):
     * Columns & Widths:
       1. Cột "Nhân viên" (20%): Avatar + Tên + Mã NV ("Nguyễn Văn An - NV001", "Trần Thị Bích - NV002", ...)
       2. Cột "Hợp đồng" (12%): "Toàn thời gian", "Toàn thời gian", "Bán thời gian", ...
       3. Cột "Mức lương/giờ" (11%): "35.000 đ/h", "30.000 đ/h", "28.000 đ/h", ...
       4. Cột "Tổng giờ" (10%): "160.0 h", "150.0 h", "100.0 h", ...
       5. Cột "Lương cơ bản" (13%): "5.600.000 ₫", "4.500.000 ₫", "2.800.000 ₫", ...
       6. Cột "Phụ cấp ca đêm" (10%): "+420.000 ₫", "+180.000 ₫", "0 ₫", ...
       7. Cột "Thưởng ca mở" (10%): "+150.000 ₫", "+80.000 ₫", "+50.000 ₫", ...
       8. Cột "Thực lĩnh" (14%): Bold primary blue "6.170.000 ₫", "4.760.000 ₫", "2.850.000 ₫", ...
   - Table Footer: "Tổng cộng" | "" | "" | "860.0 h" | "62.500.000 ₫" | "3.240.000 ₫" | "2.680.000 ₫" | "68.420.000 ₫"
   - Row Click Action: Clicking any row opens Detailed Payslip Modal GD-25.
   - Pagination: "Hiển thị 1–6 trong 6 nhân viên".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT calculate salary for resigned employees ("Nghỉ việc").
```

---

### GD-13: Báo cáo – Tổng quan báo cáo điều hành & Xuất Excel
* **Screen ID:** `GD-13` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình trung tâm báo cáo (`/manager/reports`). Quản lý xem trước số liệu của các mảng vận hành (Lịch tuần, Giờ làm, Chấm công, Bảng lương, Dashboard) và xuất tệp Excel chi tiết.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-13] Operational Reports & Excel Export Hub (/manager/reports)
Desktop web application frame showing the Manager Reports Hub. Features top summary KPI cards, chart preview grids, report type selector, period dropdown, role filters, action button "Xuất Excel", and a preview data table.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, Card White #FFFFFF, Border #E2E8F0, Slate #64748B, Background #F2F6FC.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Báo cáo" under group "Tài chính".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Báo cáo".
   - Header Row 2: Subtitle "Xem nhanh số liệu tháng hiện tại và xuất báo cáo ra file Excel: lịch làm việc, giờ làm, chấm công, bảng lương, Dashboard."
   - Report Overview KPI Strip (ReportStatsOverview):
     * Stat 1: "Tổng ca hoàn thành: 156 ca"
     * Stat 2: "Tổng giờ công tích lũy: 860.0 h"
     * Stat 3: "Tỷ lệ đi làm đúng giờ: 94.2%"
     * Stat 4: "Tổng quỹ lương đã chi: 68.420.000 ₫"
   - Report Charts Grid (ReportChartsGrid):
     * Mini Chart 1: "Cơ cấu giờ làm theo vị trí" (Pha chế: 42%, Phục vụ: 30%, Thu ngân: 20%, Trưởng ca: 8%).
     * Mini Chart 2: "Biến động chi phí lương 4 tuần gần nhất".
   - Toolbar:
     * Report Type Selector (w-60):
       Options: "Báo cáo lịch làm việc theo tuần (Roster)", "Báo cáo tổng hợp giờ làm (Hours)", "Báo cáo đối soát chấm công (Attendance)", "Báo cáo bảng tính lương (Payroll)", "Báo cáo tổng quan Dashboard"
     * Period Selector (w-36): "Tháng này" (Options: "Tuần này", "Tháng này", "Tháng trước", "Khoảng ngày tùy chọn")
     * Role Filter (w-44): "Tất cả Role_Tag" (Options: "Tất cả Role_Tag", "Barista", "Cashier", "Waiter", "Shift_Leader")
     * Button "Xóa bộ lọc"
     * Right Action: ExportButton with Excel icon: "Xuất Excel"
   - Report Preview Table (Showing first 5 preview rows of selected report type):
     * Header notice: "Bản xem trước 5 dòng đầu tiên. Bấm 'Xuất Excel' để tải toàn bộ dữ liệu."
     * Sample preview table columns according to selected report type.

STRICT NEGATIVE CONSTRAINTS:
- No breadcrumb path.
- No secondary header.
```

---

### GD-14: Trợ lý AI – Trợ lý hội thoại phân tích ca & Vận hành
* **Screen ID:** `GD-14` | **Actor:** `Quản lý (Manager)`
* **Context:** Màn hình trò chuyện với AI chuyên dụng (`/manager/assistant`). Quản lý tra cứu dữ liệu vận hành, phân tích ca trực còn thiếu và nhận đề xuất xếp lịch tối ưu bằng ngôn ngữ tự nhiên tiếng Việt.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-14] AI Scheduling Assistant Chatbot (/manager/assistant)
Desktop web application frame showing the AI Assistant Interface. Layout divided into two unequal columns: Left sidebar panel for conversation history, tips, and suggested questions; Right panel for the active chat stream, assistant message bubbles with structured data blocks, and prompt input box with suggestion chips.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Primary #3F62C4, AI Assistant Gradient linear-gradient(135deg,#3F62C4,#19B0DC), Surface White #FFFFFF, Border #E2E8F0, Chat Bubble Assistant #F8FAFC, Chat Bubble User #EEF2FB.
- Typography: "Be Vietnam Pro", Markdown code/table formatting in AI replies.

PAGE COMPOSITION & TEXT COPY:
1. SIDEBAR & TOP HEADER: Manager Portal navigation. Active sidebar item: "Trợ lý AI" under group "Công cụ".
2. PAGECARD CONTAINER:
   - Header Row 1: Title "Trợ lý AI". Right Action Button: Primary button with plus icon: "Đoạn chat mới".
   - Header Row 2: Subtitle "Hỏi đáp số liệu vận hành, phân tích ca trực và nhận đề xuất xếp lịch bằng ngôn ngữ tự nhiên."
   - 2-Column Chat Workspace:
     * Left Sidebar Panel (Width 320px, border-r border-slate-200, padding 16px):
       - Block 1: "Lịch sử hội thoại" (HistoryCard):
         List of recent chats:
         + "Kiểm tra nhân sự thiếu cuối tuần" (10 phút trước)
         + "Ước tính chi phí lương tuần 43" (Hôm qua)
         + "Danh sách đơn xin nghỉ chờ duyệt" (3 ngày trước)
       - Block 2: "Mẹo đặt câu hỏi" (TipCard):
         Bullet points: "Hỏi theo ngày cụ thể", "Hỏi theo tên nhân viên hoặc chuyên môn", "Yêu cầu so sánh tuần này với tuần trước".
       - Block 3: "Câu hỏi gợi ý" (SuggestedQuestions):
         Clickable prompt chips: "Ai nghỉ tuần này?", "Ca nào đang thiếu người?", "Tổng lương tháng này bao nhiêu?", "Có ca nào thiếu giờ ra không?".
     * Right Chat Stream Panel (Flex-1, height 600px, flex-col):
       - Messages Container (Scrollable):
         * User Bubble: Avatar "NA", Time "10:30", Text: "Ca chiều Thứ 6 tuần này đang phân bổ thế nào?"
         * AI Assistant Bubble: Avatar sparkles icon, Name: "Trợ lý Smart Scheduling", Time "10:30":
           Text reply: "Chào anh An, theo dữ liệu lịch làm việc tuần này:
           - Ca chiều Thứ 6 (12:00 - 17:00) định biên yêu cầu 2 nhân viên (1 Pha chế, 1 Thu ngân).
           - Hiện tại Thu ngân đã có bạn Lê Minh Cường (NV003) đảm nhiệm.
           - Vị trí Pha chế đang bị KHUYẾT do bạn Trần Thị Bích (NV002) có đơn nghỉ phép đột xuất đã duyệt (LV-2026-004).
           - Hệ thống đã tự động tạo Ca trực mở OS001 (+50.000 ₫) để tuyển bổ sung. Bạn Vũ Minh Thảo (NV007) đang rảnh trong khung giờ này."
           Action buttons inside bubble: [Button: "Xem ca mở OS001"] [Button: "Gợi ý phân công Vũ Minh Thảo"]
       - Quick Action Prompt Chips:
         ["Tóm tắt tuần này", "Ai nghỉ tuần này?", "Ca nào đang thiếu người?", "Báo cáo giờ làm"]
       - Input Box Bar (Bottom):
         Input field with placeholder: "Hỏi bất cứ điều gì về lịch làm việc, chấm công, bảng lương..."
         Send button with paper plane icon (Primary #3F62C4).

STRICT NEGATIVE CONSTRAINTS:
- Do NOT display generic web search answers. AI is bounded to the Smart Scheduling system database.
```

---

### GD-15: Tổng quan nhân viên
* **Screen ID:** `GD-15` | **Actor:** `Nhân viên (Staff)`
* **Context:** Màn hình tổng quan của nhân viên (`/employees/Tong-quan`). Hiển thị thẻ tóm tắt các tính năng đổi ca, lịch trực và thông báo nội bộ.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-15] Employee Overview & Shift Exchange Rules (/employees/Tong-quan)
Desktop web application frame showing the Employee Portal layout. Features Employee Dark Sidebar (width 240px, background #0B1D3F), top notification bell, and main card displaying operational overview and shift exchange guidelines.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Sidebar Dark Navy #0B1D3F, Active Nav #3F62C4/40, Background #F1F5F9, Card Surface #FFFFFF, Text #1E293B.
- Typography: "Be Vietnam Pro", Title 24px font-semibold.

PAGE COMPOSITION & TEXT COPY:
1. EMPLOYEE SIDEBAR (Width 240px, Dark Background #0B1D3F):
   - Logo at top: Smart Scheduling logo for dark mode.
   - Menu items:
     * "Tổng quan" (Active: background rgba(63,98,196,0.4), text white font-medium)
     * "Lịch của tôi" (links to GD-16)
     * "Đăng ký thời gian rảnh" (links to GD-17)
     * "Đổi ca" (links to GD-18)
     * "Ca trực mở" (links to GD-19)
     * "Nghỉ phép" (links to GD-20)
     * "Chấm công" (links to GD-21)
     * "Thông tin cá nhân" (links to GD-22)
   - Bottom Section: User name "Phạm Thị Dung", button "Đăng xuất" (text-slate-400 hover:text-white).

2. MAIN CONTENT (Padding 32px, Background #F1F5F9):
   - Top right: NotificationBell icon button.
   - Page Heading: "Đổi ca" (24px font-semibold mb-4 text-[#1E293B]).
   - Content Card (White background, rounded-lg, border border-gray-200, padding 16px):
     * Bullet points list:
       - "Gửi yêu cầu đổi ca"
       - "Nhận / xác nhận yêu cầu từ đồng nghiệp"
       - "Bị từ chối tự động nếu kỹ năng thấp hơn yêu cầu (kết quả từ API)"

STRICT NEGATIVE CONSTRAINTS:
- No Manager sidebar items (no "Tính lương", "Báo cáo", "Ca & ràng buộc").
- Exactly match the clean card specification present in the source code.
```

---

### GD-16: Lịch của tôi – Lịch làm việc cá nhân
* **Screen ID:** `GD-16` | **Actor:** `Nhân viên (Staff)`
* **Context:** Màn hình xem lịch làm việc cá nhân của nhân viên (`/employees/schedule`).
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-16] Employee Personal Schedule (/employees/schedule)
Desktop web application frame within the Employee Portal layout. Features dark navy employee sidebar, active indicator on "Lịch của tôi", and personal schedule card specifying the weekly personal calendar view.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Sidebar Dark Navy #0B1D3F, Background #F1F5F9, Card Surface #FFFFFF, Text #1E293B, Border #E2E8F0.
- Typography: "Be Vietnam Pro", Title 24px font-semibold.

PAGE COMPOSITION & TEXT COPY:
1. EMPLOYEE SIDEBAR: Dark Navy #0B1D3F. Active item: "Lịch của tôi" (icon calendar, highlighted with rgba(63,98,196,0.4)).
2. MAIN CONTENT AREA:
   - Top right: NotificationBell button.
   - Page Heading: "Lịch của tôi" (24px font-semibold text-slate-900 mb-4).
   - Content Card (White background, rounded-lg, border border-gray-200, padding 16px):
     * Bullet points list:
       - "Xem lịch làm việc của mình (dạng Calendar)"
     * UI Placeholder Frame: Weekly employee calendar grid mockup showing assigned shift blocks:
       "Ca sáng (06:00 - 12:00) Thứ 2, Thứ 4, Thứ 6" | "Ca tối (17:00 - 22:00) Chủ nhật".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT display other employees' personal wages.
```

---

### GD-17: Đăng ký thời gian rảnh – Khảo sát rảnh/bận 7 ngày
* **Screen ID:** `GD-17` | **Actor:** `Nhân viên (Staff)`
* **Context:** Màn hình đăng ký lịch rảnh hàng tuần của nhân viên (`/employees/availability`).
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-17] Employee Availability Registration (/employees/availability)
Desktop web application frame in Employee Portal layout. Active sidebar item: "Đăng ký thời gian rảnh". Card container displaying weekly availability submission controls.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Dark Navy #0B1D3F, Background #F1F5F9, Card Surface #FFFFFF, Text #1E293B.
- Typography: "Be Vietnam Pro", Title 24px font-semibold.

PAGE COMPOSITION & TEXT COPY:
1. EMPLOYEE SIDEBAR: Dark Navy #0B1D3F. Active menu: "Đăng ký thời gian rảnh" (calendarClock icon).
2. MAIN CONTENT AREA:
   - Page Heading: "Đăng ký rảnh/bận" (24px font-semibold mb-4).
   - Content Card (White background, rounded-lg, border border-gray-200, padding 16px):
     * Bullet points list:
       - "Chọn rảnh / bận cho cả 7 ngày trong tuần"
     * UI Placeholder Frame: 7-day selector grid (Thứ 2 đến Chủ nhật) with toggle pills for "Rảnh" and "Bận" across Sáng, Chiều, Tối.

STRICT NEGATIVE CONSTRAINTS:
- No manager-only configuration toggles.
```

---

### GD-18: Đổi ca nhân viên – Hub đổi ca, hoán đổi & nhờ làm thay
* **Screen ID:** `GD-18` | **Actor:** `Nhân viên (Staff)`
* **Context:** Màn hình đổi ca hoàn chỉnh của nhân viên (`/employees/shift-exchange`). Nhân viên xem các yêu cầu gửi đến mình cần phản hồi, các yêu cầu mình đã gửi, xem trạng thái phê duyệt và bấm "Tạo yêu cầu đổi ca" mới.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-18] Employee Shift Exchange Hub (/employees/shift-exchange)
Full desktop view (1440px wide) featuring Employee Layout with dark navy sidebar (#0B1D3F), top notification bell, and the full interactive Shift Exchange Workspace. Includes tabs ("Gửi đến tôi", "Tôi đã gửi", "Tất cả"), pending response banners, "Tạo yêu cầu đổi ca" button, coworker exchange cards with accept/reject buttons.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Employee Sidebar #0B1D3F, Primary Brand #3F62C4, Green #10B981, Red #D64550, Amber #F59E0B, Surface White #FFFFFF, Background #F1F5F9.
- Typography: "Be Vietnam Pro", Body 14px font-normal, Subtitles 13px font-medium.

PAGE COMPOSITION & TEXT COPY:
1. EMPLOYEE SIDEBAR: Dark Navy #0B1D3F. Active item: "Đổi ca" (swap icon). User tag: "Phạm Thị Dung (NV004)".
2. MAIN CONTENT AREA:
   - Top Bar:
     * Heading h1: "Đổi ca làm việc" (24px font-bold text-slate-900)
     * Subtitle: "Gửi yêu cầu hoán đổi ca hoặc nhờ đồng nghiệp trực thay. Hệ thống chỉ cho phép đổi với đồng nghiệp cùng chuyên môn (Role_Tag)."
     * Top Right CTA: Button primary (Solid Blue #3F62C4, white text, plus icon): "Tạo yêu cầu đổi ca" (triggers GD-27).
   - Staff Switcher for Testing (Inline banner):
     "Đang xem với tư cách nhân viên:" Dropdown selector: "NV004 - Phạm Thị Dung (Pha chế)"
   - Navigation Tabs Bar:
     * Tab 1 (Active): Inbox icon + "Gửi đến tôi" (Badge count: "1" in red pill)
     * Tab 2: Send icon + "Tôi đã gửi" (Badge count: "2")
     * Tab 3: History icon + "Tất cả yêu cầu"
   - Active Tab Content ("Gửi đến tôi" Tab):
     * Exchange Request Card 1 (Chờ phản hồi từ bạn):
       - Header: Status badge amber: "Chờ đồng nghiệp" | Request code: "EX-2026-006" | Time: "20/10/2026 11:20"
       - Body Grid (2 Columns comparison):
         * Column A: "Ca đồng nghiệp nhờ bạn trực thay:"
           Card: "Trần Thị Bích (NV002 - Pha chế)" | "Ca chiều (12:00 - 17:00)" | "Thứ 6, 24/10/2026"
         * Column B: "Hình thức đổi ca:"
           Pill: "Nhờ làm thay (Cover)" | Reason: "Nhờ chị Dung trực thay ca chiều Thứ 6 vì bận việc gia đình."
       - Actions Bar (Bottom of card):
         * Button 1 (Green solid #10B981, text white): CheckCircle2 icon + "Đồng ý hỗ trợ"
         * Button 2 (Outline gray): X icon + "Từ chối"
   - Outgoing Requests Section (Preview):
     * Request Card 2:
       Status badge blue: "Chờ quản lý duyệt" | "EX-2026-005" | "Hoán đổi ca với Hoàng Văn Nam" | Action button: "Hủy yêu cầu".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT allow cross-role exchanges (Barista cannot exchange with Cashier).
```

---

### GD-19: Ca trực mở nhân viên – Nhận ca tăng cường
* **Screen ID:** `GD-19` | **Actor:** `Nhân viên (Staff)`
* **Context:** Màn hình nhận ca trống của nhân viên (`/employees/open-shifts`).
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-19] Employee Open Shift Marketplace (/employees/open-shifts)
Desktop web application frame within Employee Portal layout. Active sidebar item: "Ca trực mở". White card specifying open shift pickup rules and eligibility.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Dark Navy #0B1D3F, Background #F1F5F9, Card Surface #FFFFFF, Text #1E293B.
- Typography: "Be Vietnam Pro", Title 24px font-semibold.

PAGE COMPOSITION & TEXT COPY:
1. EMPLOYEE SIDEBAR: Dark Navy #0B1D3F. Active item: "Ca trực mở" (userPlus icon).
2. MAIN CONTENT AREA:
   - Page Heading: "Ca trống" (24px font-semibold mb-4).
   - Content Card (White background, rounded-lg, border border-gray-200, padding 16px):
     * Bullet points list:
       - "Xem ca trống"
       - "Nhận ca"
       - "Nếu người khác nhận trước: hiện thông báo lỗi"
     * UI Placeholder Frame: Available open shifts cards with "+50.000 ₫" bonus pill and "Nhận ca này" action button.

STRICT NEGATIVE CONSTRAINTS:
- No manager edit/cancel buttons.
```

---

### GD-20: Nghỉ phép nhân viên – Nộp đơn xin nghỉ đột xuất
* **Screen ID:** `GD-20` | **Actor:** `Nhân viên (Staff)`
* **Context:** Màn hình nộp đơn xin nghỉ phép của nhân viên (`/employees/leave`).
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-20] Employee Leave Request Submission (/employees/leave)
Desktop web application frame in Employee Portal layout. Active sidebar item: "Nghỉ phép". White card specifying emergency leave submission and cancellation lock rules.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Dark Navy #0B1D3F, Background #F1F5F9, Card Surface #FFFFFF, Text #1E293B.
- Typography: "Be Vietnam Pro", Title 24px font-semibold.

PAGE COMPOSITION & TEXT COPY:
1. EMPLOYEE SIDEBAR: Dark Navy #0B1D3F. Active item: "Nghỉ phép" (fileText icon).
2. MAIN CONTENT AREA:
   - Page Heading: "Nghỉ phép" (24px font-semibold mb-4).
   - Content Card (White background, rounded-lg, border border-gray-200, padding 16px):
     * Bullet points list:
       - "Gửi đơn nghỉ đột xuất"
       - "Xem kết quả"
       - "Nút Hủy đơn bị vô hiệu sau khi đã xác nhận người thay"
     * UI Placeholder Frame: Leave request form mockup (Chọn ca xin nghỉ, Loại nghỉ phép: "Nghỉ ốm đột xuất", Lý do, Nút "Gửi đơn xin nghỉ").

STRICT NEGATIVE CONSTRAINTS:
- No manager approve/reject controls.
```

---

### GD-21: Chấm công nhân viên – Check-in / Check-out ca làm
* **Screen ID:** `GD-21` | **Actor:** `Nhân viên (Staff)`
* **Context:** Màn hình chấm công cá nhân của nhân viên (`/employees/attendance`).
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-21] Employee Shift Check-in & Time Punch (/employees/attendance)
Desktop web application frame in Employee Portal layout. Active sidebar item: "Chấm công". White card specifying check-in/out procedures and status feedback.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Dark Navy #0B1D3F, Background #F1F5F9, Card Surface #FFFFFF, Text #1E293B.
- Typography: "Be Vietnam Pro", Title 24px font-semibold.

PAGE COMPOSITION & TEXT COPY:
1. EMPLOYEE SIDEBAR: Dark Navy #0B1D3F. Active item: "Chấm công" (clipboardCheck icon).
2. MAIN CONTENT AREA:
   - Page Heading: "Chấm công" (24px font-semibold mb-4).
   - Content Card (White background, rounded-lg, border border-gray-200, padding 16px):
     * Bullet points list:
       - "Check-in / check-out từng ca"
       - "Hiện kết quả: hợp lệ / trễ / ngoài khu vực"
     * UI Placeholder Frame: Punch card widget with real-time clock, large primary button: "Chấm công vào ca (Check-in)", status pill: "Hợp lệ".

STRICT NEGATIVE CONSTRAINTS:
- No manual punch adjustment overrides for staff.
```

---

### GD-22: Thông tin cá nhân – Hồ sơ nhân viên
* **Screen ID:** `GD-22` | **Actor:** `Nhân viên (Staff)`
* **Context:** Màn hình xem và chỉnh sửa thông tin cá nhân của nhân viên (`/employees/profile`).
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-22] Employee Personal Profile (/employees/profile)
Desktop web application frame in Employee Portal layout. Active sidebar item: "Thông tin cá nhân". White card specifying personal information view and edit.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Web Portal
- Colors: Dark Navy #0B1D3F, Background #F1F5F9, Card Surface #FFFFFF, Text #1E293B.
- Typography: "Be Vietnam Pro", Title 24px font-semibold.

PAGE COMPOSITION & TEXT COPY:
1. EMPLOYEE SIDEBAR: Dark Navy #0B1D3F. Active item: "Thông tin cá nhân" (user icon).
2. MAIN CONTENT AREA:
   - Page Heading: "Thông tin cá nhân" (24px font-semibold mb-4).
   - Content Card (White background, rounded-lg, border border-gray-200, padding 16px):
     * Bullet points list:
       - "Xem và sửa thông tin cá nhân"
     * UI Placeholder Frame: Profile view form showing: Họ tên: "Phạm Thị Dung", Email: "phamthidung@gmail.com", Số điện thoại: "0905123459", Vị trí: "Pha chế", Loại hợp đồng: "Bán thời gian".

STRICT NEGATIVE CONSTRAINTS:
- Staff cannot modify their own hourly wage.
```

---

### GD-23: Modal Tạo lịch tự động OR-Tools & Điều chỉnh ca trực
* **Screen ID:** `GD-23` | **Actor:** `Quản lý (Manager)`
* **Context:** Hộp thoại popup khi kích hoạt OR-Tools hoặc chỉnh sửa thủ công nhân sự ca trực tại màn Tạo lịch tự động (`/manager/schedule`).
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-23] Auto-Generate OR-Tools & Shift Edit Modal Dialogs (Schedule Modal)
Standalone modal dialog frame (640px wide modal card centered over dark backdrop rgba(0,0,0,0.4)). Contains tabs for OR-Tools algorithm progress and manual employee shift assignment adjustment.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Modal Dialog (Centered overlay)
- Colors: Modal Surface White #FFFFFF, Backdrop rgba(0,0,0,0.4), Primary #3F62C4, Green #10B981, Danger #D64550, Border #E2E8F0.
- Typography: "Be Vietnam Pro", Modal Header 18px font-semibold.

PAGE COMPOSITION & TEXT COPY:
1. MODAL DIALOG CONTAINER (Width 640px, rounded-2xl, background #FFFFFF, shadow-2xl, padding 24px):
   - Header:
     Title h3: "Tạo lịch tự động bằng Google OR-Tools" (18px font-semibold #0E1A3A)
     Close button: "✕" icon on top right.
   - Body Content:
     * Progress State Box:
       - Stepper 1: "Kiểm tra 6/7 nhân viên nộp lịch rảnh" (Completed - Green check icon)
       - Stepper 2: "Khởi chạy solver OR-Tools với 4 ràng buộc" (In Progress - Blue spinner)
       - Stepper 3: "Phân bổ 21 ca làm việc trong tuần" (Pending)
     * Success Summary Card (Post-run):
       - Headline: "Đã tạo lịch thành công cho tuần 20/10 – 26/10/2026!" (Green text)
       - Metrics: "19/21 ca đã đủ người (90.5%)" | "2 ca còn thiếu người (Chiều T3, Chiều T6)"
     * Action Buttons (Bottom):
       - Secondary Button: "Đóng"
       - Primary Button: "Áp dụng vào bảng phân ca"
2. ALTERNATIVE STATE: "Điều chỉnh nhân sự ca trực" (EditShiftModal):
   - Header: "Điều chỉnh ca: Ca chiều (12:00 - 17:00), Thứ 6"
   - Current Staff List: "Trần Thị Bích (Pha chế)" with red "Gỡ bỏ" icon button.
   - Add Staff Dropdown: "Chọn nhân viên có kỹ năng phù hợp..." (Shows "Vũ Minh Thảo - Pha chế - Rảnh").
   - Action Buttons: Button "Hủy" | Button "Lưu thay đổi".

STRICT NEGATIVE CONSTRAINTS:
- No full page sidebar inside this modal frame.
```

---

### GD-24: Modal Điều chỉnh chấm công & Lịch sử sửa đổi
* **Screen ID:** `GD-24` | **Actor:** `Quản lý (Manager)`
* **Context:** Hộp thoại điều chỉnh chấm công tại màn Chấm công (`/manager/attendance`). Quản lý sửa giờ check-in / check-out thực tế, nhập lý do bắt buộc và xem lịch sử audit trail.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-24] Attendance Adjustment & Audit Trail Modal Dialog
Standalone modal dialog frame (580px wide modal card centered over dark backdrop rgba(0,0,0,0.4)). Displays the adjustment form for modifying actual punch-in and punch-out times, mandatory reason textarea, and historical audit trail entries.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Modal Dialog
- Colors: Modal Surface White #FFFFFF, Primary #3F62C4, Danger #D64550, Border #DCE6F5, Input Bg #F7FAFF.
- Typography: "Be Vietnam Pro", Labels 13px font-medium.

PAGE COMPOSITION & TEXT COPY:
1. MODAL DIALOG CONTAINER (Width 580px, rounded-2xl, background #FFFFFF, shadow-2xl, padding 24px):
   - Header:
     Title h3: "Điều chỉnh chấm công nhân viên" (18px font-semibold #0E1A3A)
     Close button: "✕"
   - Employee & Shift Summary Card (Bg #F8FAFC, rounded-xl, padding 12px, border border-slate-200 mb-4):
     * "Nhân viên: Lê Minh Cường (NV003 - Thu ngân)"
     * "Ca làm việc: Ca chiều (12:00 - 17:00), Thứ 3, 21/10/2026"
     * "Kế hoạch: 12:00 – 17:00 (5.0 giờ)"
     * "Hiện tại: Vào 11:55 – Chưa chấm ra (Quên checkout)"
   - Adjustment Form:
     * Field 1: "Giờ vào thực tế (Check-in)"
       Input type time: "11:55"
     * Field 2: "Giờ ra thực tế (Check-out)"
       Input type time: "17:05" (Red border if empty: "Vui lòng nhập đủ giờ check-out.")
     * Field 3: "Lý do điều chỉnh (Bắt buộc)"
       Textarea with placeholder: "Nhập lý do điều chỉnh chấm công (vd: Nhân viên quên bấm vân tay khi ra ca)..."
   - Audit Trail History Section:
     * Label: "Lịch sử điều chỉnh:"
     * Entry: "Chưa có lần chỉnh sửa nào trước đó."
   - Footer Action Buttons:
     * Button "Hủy" (Secondary outline)
     * Button "Lưu điều chỉnh" (Primary Solid #3F62C4, white text).

STRICT NEGATIVE CONSTRAINTS:
- Do NOT allow saving without reason.
```

---

### GD-25: Modal Phiếu lương chi tiết nhân viên
* **Screen ID:** `GD-25` | **Actor:** `Quản lý (Manager)`
* **Context:** Hộp thoại xem phiếu lương chi tiết của nhân viên tại màn Tính lương (`/manager/payroll`). Bóc tách chi tiết từng ca làm việc, giờ công, đơn giá, phụ cấp và thực lĩnh.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-25] Employee Detailed Payslip Breakdown Modal Dialog
Standalone modal dialog frame (720px wide modal card centered over dark backdrop rgba(0,0,0,0.4)). Displays an employee's comprehensive monthly payslip breakdown, including basic hours, night shift multipliers, open shift bonuses, and total payout calculation.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Modal Dialog
- Colors: Modal White #FFFFFF, Primary #3F62C4, Green #10B981, Border #E2E8F0.
- Currency: Vietnamese Dong (₫).

PAGE COMPOSITION & TEXT COPY:
1. MODAL DIALOG CONTAINER (Width 720px, rounded-2xl, background #FFFFFF, shadow-2xl, padding 24px):
   - Header:
     Title h3: "Phiếu lương chi tiết nhân viên" (18px font-semibold #0E1A3A)
     Close button: "✕"
   - Employee Profile Banner:
     * Avatar circle "NA", Name: "Nguyễn Văn An" | Mã NV: "NV001" | Vị trí: "Quản lý ca" | Hợp đồng: "Toàn thời gian"
     * Mức lương giờ cơ bản: "35.000 đ/h" | Kỳ lương: "Tháng 10/2026"
   - Salary Breakdown Summary Grid (4 Mini Cards):
     * Card 1: "Tổng giờ làm" -> "160.0 h"
     * Card 2: "Lương cơ bản" -> "5.600.000 ₫"
     * Card 3: "Phụ cấp ca đêm (x1.2)" -> "+420.000 ₫" (60h đêm)
     * Card 4: "Thưởng ca mở" -> "+150.000 ₫" (3 ca mở)
   - Final Total Banner (Bg #EEF2FB, rounded-xl, padding 16px, border border-[#BFD0F5]):
     * Label: "TỔNG THỰC LĨNH:"
     * Big Amount: "6.170.000 ₫" (Bold 24px, color #314FA4)
   - Shift Ledger Table (List of shifts worked in the period):
     Columns: "Ngày", "Ca làm việc", "Giờ vào/ra", "Số giờ", "Thành tiền"
     Sample row: "20/10/2026" | "Ca sáng" | "05:58 - 12:02" | "6.0 h" | "210.000 ₫"
   - Footer: Button "Đóng" | Button "In phiếu lương (PDF)".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT alter base wage if payroll period is locked.
```

---

### GD-26: Modal Chi tiết đơn nghỉ & Bảng AI gợi ý người thay ca
* **Screen ID:** `GD-26` | **Actor:** `Quản lý (Manager)`
* **Context:** Hộp thoại chi tiết đơn nghỉ phép tại màn Đơn xin nghỉ (`/manager/leave`). Tích hợp tính năng AI phân tích ma trận lịch rảnh để gợi ý các nhân viên thay thế phù hợp nhất theo chuyên môn.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-26] Leave Request Details & AI Replacement Matcher Modal Dialog
Standalone modal dialog frame (680px wide modal card centered over dark backdrop rgba(0,0,0,0.4)). Displays full leave request information alongside an AI candidate matching panel with suitability percentage scores and one-click replacement selection.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Modal Dialog
- Colors: Modal White #FFFFFF, Primary #3F62C4, Sparkle Purple #8B5CF6, Green Match #10B981, Border #E2E8F0.
- Typography: "Be Vietnam Pro".

PAGE COMPOSITION & TEXT COPY:
1. MODAL DIALOG CONTAINER (Width 680px, rounded-2xl, background #FFFFFF, shadow-2xl, padding 24px):
   - Header:
     Title h3: "Chi tiết đơn xin nghỉ & Gợi ý thay thế AI" (18px font-semibold #0E1A3A)
     Close button: "✕"
   - Request Info Card (Bg #F8FAFC, rounded-xl, padding 16px, border border-slate-200 mb-4):
     * "Mã đơn: LV-2026-001" | Status pill: "Chờ duyệt"
     * "Nhân viên xin nghỉ: Trần Thị Bích (NV002 - Pha chế)"
     * "Ca xin nghỉ: Ca sáng (06:00 - 12:00), Thứ 7, 25/10/2026"
     * "Lý do: Bị cảm sốt cần nghỉ ngơi và đi khám tại bệnh viện vào sáng Thứ 7."
   - AI Replacement Candidates Section:
     * Title with sparkles icon: "AI gợi ý nhân sự thay thế phù hợp nhất (Cùng kỹ năng Pha chế & Đang rảnh):"
     * Candidate Card 1 (High match):
       - Avatar "VT", Name: "Vũ Minh Thảo (NV007 - Pha chế)"
       - Match Score Pill: "Phù hợp 95%" (Green bg #ECFDF5, text #047857)
       - Availability note: "Rảnh ca sáng T7, chưa làm quá 25h trong tuần."
       - Action Button: Primary #3F62C4 "Chọn người này thay ca"
     * Candidate Card 2:
       - Avatar "DY", Name: "Đặng Hoàng Yến (NV008 - Phục vụ)"
       - Match Score Pill: "Phù hợp 60%" (Chưa có chuyên môn Barista)
   - Alternative Action: Button (Outline): "Duyệt nghỉ và tạo Ca trực mở (Open Shift)"
   - Footer Buttons:
     * Button "Đóng"
     * Button (Red): "Từ chối đơn"
     * Button (Green): "Phê duyệt đơn này".

STRICT NEGATIVE CONSTRAINTS:
- Do NOT recommend candidates who are already assigned to a concurrent shift.
```

---

### GD-27: Modal Tạo yêu cầu đổi ca nhân viên
* **Screen ID:** `GD-27` | **Actor:** `Nhân viên (Staff)`
* **Context:** Hộp thoại tạo yêu cầu đổi ca tại cổng nhân viên (`/employees/shift-exchange`). Nhân viên chọn ca của mình, chọn đồng nghiệp cùng chuyên môn, chọn hình thức Hoán đổi ca (Swap) hoặc Nhờ làm thay (Cover), và nhập lý do.
* **Google Stitch Master Prompt:**
```text
FRAME TITLE: [GD-27] Create Employee Shift Exchange Request Modal Dialog
Standalone modal dialog frame (600px wide modal card centered over dark backdrop rgba(0,0,0,0.4)). Contains the step-by-step form for submitting a shift exchange or shift cover request to a colleague of the same role tag.

DESIGN SYSTEM (STRICT):
All visible UI text must be rendered in Vietnamese with full diacritics exactly as quoted. Use a font that fully supports Vietnamese.
- Platform: Desktop Modal Dialog
- Colors: Modal White #FFFFFF, Primary #3F62C4, Border #DCE6F5, Input Bg #F7FAFF, Text #0E1A3A.
- Typography: "Be Vietnam Pro", Labels 13px font-medium.

PAGE COMPOSITION & TEXT COPY:
1. MODAL DIALOG CONTAINER (Width 600px, rounded-2xl, background #FFFFFF, shadow-2xl, padding 24px):
   - Header:
     Title h3: "Tạo yêu cầu đổi ca làm việc" (18px font-semibold #0E1A3A)
     Close button: "✕"
   - Form Fields:
     * Field 1: "1. Chọn ca của bạn cần đổi (Bắt buộc)"
       Select dropdown: "Ca chiều (12:00 - 17:00), Thứ 6, 24/10/2026"
     * Field 2: "2. Chọn hình thức đổi ca"
       Two Radio Option Cards:
       - Card A (Selected): Radio "Hoán đổi ca (Swap)" | Desc: "Bạn và đồng nghiệp đổi ca cho nhau."
       - Card B: Radio "Nhờ làm thay (Cover)" | Desc: "Đồng nghiệp trực thay bạn (bạn không trực ca nào của họ)."
     * Field 3: "3. Chọn đồng nghiệp muốn đổi ca (Cùng chuyên môn Pha chế)"
       Select dropdown: "Trần Thị Bích (NV002 - Pha chế)"
       (Helper note: "Chỉ hiển thị đồng nghiệp có cùng Role_Tag với bạn theo quy định.")
     * Field 4: "4. Chọn ca của đồng nghiệp bạn muốn nhận (Nếu chọn Hoán đổi ca)"
       Select dropdown: "Ca sáng (06:00 - 12:00), Thứ 7, 25/10/2026"
     * Field 5: "5. Lý do đổi ca"
       Textarea with placeholder: "Nhập lý do đổi ca (vd: Bận việc gia đình, trùng lịch thi...)"
   - Validation Error Notice (Conditional):
     Soft red banner: "Vui lòng chọn đầy đủ ca làm việc và đồng nghiệp muốn đổi!"
   - Footer Action Buttons:
     * Button "Hủy" (Secondary)
     * Button "Gửi yêu cầu" (Primary Solid #3F62C4, white text).

STRICT NEGATIVE CONSTRAINTS:
- Do NOT display coworkers with different role tags.
```

---

# PHẦN III – HƯỚNG DẪN SỬ DỤNG VỚI GOOGLE STITCH & FIGMA

### BƯỚC 1: TẠO DỰ ÁN TRÊN GOOGLE STITCH
1. Truy cập [stitch.withgoogle.com](https://stitch.withgoogle.com) và đăng nhập bằng tài khoản Google.
2. Tạo một Workspace hoặc Canvas mới cho dự án **Smart Scheduling System**.

### BƯỚC 2: DÁN PROMPT TỪNG MÀN HÌNH ĐỘC LẬP
1. Mở file `PROMPTS_GOOGLE_STITCH_MASTER.md`.
2. Chọn màn hình cần tạo (ví dụ `GD-00` hoặc `GD-01`).
3. Sao chép **toàn bộ khối mã text** bên trong mục `Google Stitch Master Prompt` của màn hình đó (từ dòng `FRAME TITLE: [GD-XX] ...` đến hết dòng `STRICT NEGATIVE CONSTRAINTS`).
4. Dán trực tiếp vào ô nhập prompt của Google Stitch và nhấn **Generate**.
5. Google Stitch sẽ đọc các token Design System và xuất ra đúng một Frame hoàn chỉnh với đúng giao diện, màu sắc, font chữ và các đoạn văn bản tiếng Việt có dấu.

### BƯỚC 3: XUẤT SANG FIGMA (EXPORT TO FIGMA)
1. Sau khi Stitch sinh ra giao diện, bấm vào nút **Export** ở góc trên bên phải màn hình Stitch.
2. Chọn **Copy to Figma** (hoặc Download Figma File).
3. Mở file thiết kế của bạn trên Figma và nhấn `Ctrl + V` (`Cmd + V` trên macOS).
4. Frame giao diện sẽ được dán vào Figma dưới dạng các Vector layer, Auto Layout và Text layer có thể chỉnh sửa 100%.
5. Lặp lại cho các màn hình từ `GD-00` đến `GD-27` để có trọn bộ UI Prototype hoàn chỉnh và đồng bộ cho hệ thống Smart Scheduling.
