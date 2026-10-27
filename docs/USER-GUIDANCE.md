# Hướng dẫn sử dụng và tour thao tác

Mở **Hướng dẫn sử dụng** trong menu hoặc biểu tượng dấu hỏi trên thanh trên để vào `/help`. Tìm kiếm hỗ trợ tiếng Việt có dấu và không dấu; có thể kết hợp với nhóm chủ đề. Các bài được lọc theo vai trò và quyền hiện tại. Bài viết không thay thế kiểm tra quyền tại API.

Mỗi bài có hình minh họa phóng to, bốn bước thao tác, ghi chú, bài liên quan và liên kết tới chức năng. Hình SVG mô tả luồng với dữ liệu mẫu; bài Android có ảnh màn hình ứng dụng thật với dữ liệu demo. Nhãn ảnh phân biệt minh họa với dữ liệu của người dùng. Dấu “đã hiểu” được lưu riêng trên trình duyệt theo tài khoản, công ty và vai trò.

Lần đầu về trang tổng quan hoặc trung tâm hướng dẫn, hộp chào mừng mời bắt đầu tour. Tour tô sáng khu vực hiện tại, chuyển trang theo từng bước và mở menu trên màn hình nhỏ khi giới thiệu điều hướng. Có thể dùng nút trước/sau, phím mũi tên, Esc hoặc bỏ qua. Tour chỉ điều hướng; không tự tạo nhân viên, gửi yêu cầu, duyệt công hay phát hành lương. Nếu phần tử chưa tải, người dùng có thể thử lại hoặc đi tiếp. Bỏ qua và hoàn thành được ghi nhớ; có thể chủ động mở lại bất cứ lúc nào.

## API và triển khai

Chạy `php backend/spark migrate` trước khi phát hành web mới. Migration `2026-10-27-000005_GuidancePreferences` thêm bảng tùy chọn, không sửa dữ liệu nghiệp vụ.

- `GET /api/v1/me/guidance`: trả `version`, `status`, `updated_at`; chưa xem có `version: 0`, `status: new`.
- `PUT /api/v1/me/guidance`: nhận `{ "version": 1, "status": "dismissed" }` hoặc `completed`.

API lấy user, tenant và role từ phiên xác thực; không nhận ID để truy cập tùy chọn của người khác. Quản trị nền tảng dùng scope riêng. Trạng thái tour có bản dự phòng localStorage khi mất kết nối hoặc API lỗi; sẽ đồng bộ khi có lần tải sau. Ngày giờ hiển thị giữ chuẩn Việt Nam `dd/MM/yyyy HH:mm:ss`; timestamp API vẫn theo hợp đồng ISO hiện có.

## Kiểm tra

`npm test --prefix web` bao gồm tìm kiếm không dấu, lọc chủ đề, giới hạn nội dung theo vai trò, liên kết bài, vị trí coach trên mobile, ảnh phóng to, đánh dấu đã hiểu, lời chào lần đầu, ghi nhớ bỏ qua và chạy lại tour. `node tests/api.integration.mjs` kiểm tra xác thực, lưu trạng thái, cách ly tài khoản/công ty, quản trị nền tảng và validation. PHPUnit kiểm tra thêm phạm vi vai trò và dữ liệu đầu vào giả mạo.

Browser của phiên triển khai không khả dụng; chưa xác nhận hình thức bằng ảnh chụp trình duyệt. Build TypeScript/Vite và kiểm thử DOM đã chạy thành công.
