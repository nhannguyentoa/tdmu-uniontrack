# Hướng dẫn triển khai TDMU UnionTrack lên Internet (miễn phí)

Dùng **Render.com** (chạy ứng dụng, gói Free) + **Clever Cloud** (MySQL, gói Free). Cả hai đều không cần thẻ tín dụng.

## Bước 1 — Tạo database MySQL miễn phí (Clever Cloud)

1. Vào https://console.clever-cloud.com → Đăng ký tài khoản (miễn phí).
2. **Create** → **Add-on** → chọn **MySQL** → chọn gói **DEV** (miễn phí).
3. Đặt tên (ví dụ `tdmu-union-db`) → Create.
4. Vào add-on vừa tạo → tab **Information** / **Environment variables**, ghi lại 5 giá trị:
   - `MYSQL_ADDON_HOST` → dùng cho `DB_HOST`
   - `MYSQL_ADDON_PORT` → dùng cho `DB_PORT`
   - `MYSQL_ADDON_DB` → dùng cho `DB_DATABASE`
   - `MYSQL_ADDON_USER` → dùng cho `DB_USERNAME`
   - `MYSQL_ADDON_PASSWORD` → dùng cho `DB_PASSWORD`

## Bước 2 — Đẩy code lên GitHub

```bash
cd laravel_app
git remote add origin https://github.com/<username>/<ten-repo>.git
git branch -M main
git push -u origin main
```
(Tạo repo trống trước tại github.com/new, không tick "Add README" để tránh xung đột.)

## Bước 3 — Tạo Web Service trên Render

1. Vào https://render.com → Đăng ký (miễn phí, có thể đăng nhập bằng GitHub luôn).
2. **New** → **Blueprint** → chọn repo GitHub vừa push (Render tự đọc file `render.yaml` đã có sẵn trong repo).
3. Render sẽ hỏi bạn điền các biến môi trường đánh dấu `sync: false`. Trước tiên tự tạo một `APP_KEY` riêng (không dùng chung với máy local, tránh lộ khóa mã hóa khi repo public trên GitHub) bằng lệnh sau trên máy bạn:
   ```
   php artisan key:generate --show
   ```
   Rồi điền các biến như sau:

| Biến | Giá trị |
|---|---|
| `APP_KEY` | kết quả lệnh trên (dạng `base64:...`) |
| `APP_URL` | `https://<ten-service>.onrender.com` (Render cho biết URL này khi tạo service — có thể để trống rồi sửa lại sau khi biết URL thật) |
| `DB_HOST` | giá trị `MYSQL_ADDON_HOST` từ Clever Cloud |
| `DB_PORT` | giá trị `MYSQL_ADDON_PORT` |
| `DB_DATABASE` | giá trị `MYSQL_ADDON_DB` |
| `DB_USERNAME` | giá trị `MYSQL_ADDON_USER` |
| `DB_PASSWORD` | giá trị `MYSQL_ADDON_PASSWORD` |

4. Bấm **Apply/Deploy**. Render sẽ build Docker image (~5-10 phút lần đầu) rồi khởi động container.
5. Theo dõi tab **Logs** — khi thấy dòng `San sang, lang nghe tren cong ...` là web đã chạy.
6. Mở `https://<ten-service>.onrender.com` — đăng nhập bằng tài khoản mẫu như trên máy bạn (`admin@tdmu.edu.vn` / `Admin@123`).

## Lưu ý quan trọng về giới hạn gói miễn phí

- **Web service "ngủ" sau 15 phút không có ai truy cập** — lần mở lại đầu tiên sẽ mất khoảng 30-50 giây để "thức dậy", sau đó chạy bình thường.
- **Database (MySQL trên Clever Cloud) lưu trữ bền vững thật sự** — dữ liệu bạn thêm/sửa/xóa qua giao diện web sẽ không mất khi container khởi động lại. `docker-entrypoint.sh` chỉ chạy `migrate` (áp dụng migration còn thiếu) và seed dữ liệu mẫu **đúng 1 lần** lúc database còn trống, không xóa dữ liệu đã có ở các lần khởi động sau.
- **Ảnh/minh chứng upload thì vẫn bị mất khi container khởi động lại** — vì chúng lưu trên ổ đĩa của container (ephemeral), không phải trên Clever Cloud. Muốn lưu file bền vững cần đấu nối dịch vụ lưu trữ ngoài (S3/Cloudinary) — ngoài phạm vi bản demo miễn phí này.

## Cập nhật code sau này

Mỗi lần push code mới lên nhánh `main` trên GitHub, Render tự động build và deploy lại (Auto-Deploy mặc định bật).
