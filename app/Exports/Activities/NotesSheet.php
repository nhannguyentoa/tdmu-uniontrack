<?php

namespace App\Exports\Activities;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Ghi chú": cách đọc và cách tính của từng sheet, thông tin lần xuất, danh sách cảnh báo dữ liệu.
 */
class NotesSheet extends ActivitySheet
{
    public const TITLE = 'Ghi chú';

    public function title(): string
    {
        return self::TITLE;
    }

    protected function build(Worksheet $ws): void
    {
        $details = $this->data->details();
        $warnings = $details
            ->map(fn ($a) => [$a, ActivityAggregator::warnings($a)])
            ->filter(fn ($pair) => $pair[1] !== []);

        $lines = [
            ['GHI CHÚ VÀ CÁCH TÍNH', 'title'],
            ['1. "Bảng 2 chiều": hàng là hoạt động, gom theo tên trên toàn trường (bỏ khoảng trắng thừa, không phân biệt hoa/thường); cột là tổ công đoàn. Ô "Số lần" là số lần tổ đó tổ chức hoạt động, ô "Điểm đạt" là tổng điểm đạt của các lần đó.', null],
            ['2. "Theo tổ công đoàn": các hoạt động cùng tên trong cùng một tổ được gom thành một dòng; số lần lớn hơn 1 được tô đỏ, mã và loại của từng lần ghi ở cột Mã hoạt động, Loại hoạt động (mỗi giá trị một dòng). Tên hiển thị là tên của lần tổ chức sớm nhất; Tiến độ TB là trung bình tiến độ các lần.', null],
            ['3. Tỷ lệ tham gia = SL thực tế / SL dự kiến. Tỷ lệ hoàn thành = Đã hoàn thành / (Tổng lượt − Đã hủy). Hạng xếp theo Tỷ lệ hoàn thành.', null],
            ['4. Điểm đạt = Điểm tối đa × Tiến độ ÷ 100, chỉ được tính khi hoạt động bật "Tính điểm thi đua", ở trạng thái Đã hoàn thành và đã được Quản trị viên duyệt điểm; các trường hợp còn lại là 0. Hoạt động đã hoàn thành nhưng chưa được duyệt ghi "Chờ duyệt điểm" ở cột Cảnh báo dữ liệu.', null],
            ['5. Số liệu lấy từ hệ thống tại thời điểm xuất. Các dòng tổng (Cộng tổ, TỔNG CỘNG, Tổng theo tổ…) là công thức nên tự cập nhật khi sửa số trong bảng.', null],
            ['', null],
            ['THÔNG TIN XUẤT FILE', 'heading'],
            ['Thời điểm xuất: '.$this->meta['exported_at']->format('d/m/Y H:i'), null],
            ['Người xuất: '.$this->meta['exported_by'], null],
            ['Bộ lọc: '.$this->meta['filters'], null],
            ['Số hoạt động: '.$details->count(), null],
            ['', null],
            ['CẢNH BÁO DỮ LIỆU ('.$warnings->count().' dòng)', 'heading'],
        ];

        foreach ($warnings as [$activity, $items]) {
            $lines[] = ['• '.$activity->code.' – '.$activity->name.' ('.$activity->unionGroup?->name.'): '.implode('; ', $items), 'warning'];
        }
        if ($warnings->isEmpty()) {
            $lines[] = ['Không có cảnh báo nào.', null];
        }

        $this->writeGrid($ws, array_map(fn ($l) => [$l[0] === '' ? null : $l[0]], $lines));

        $ws->getColumnDimension('A')->setWidth(110);
        $this->align($ws, 'A1:A'.count($lines), Alignment::HORIZONTAL_LEFT);
        foreach ($lines as $i => [, $kind]) {
            $font = $ws->getStyle('A'.($i + 1))->getFont();
            match ($kind) {
                'title' => $font->setSize(15)->setBold(true)->getColor()->setRGB(self::NAVY),
                'heading' => $font->setBold(true)->getColor()->setRGB(self::NAVY),
                'warning' => $font->getColor()->setRGB(self::RED),
                default => null,
            };
        }

        $this->autoRowHeights($ws, 1, count($lines));
        $this->pageSetup($ws);
    }
}
