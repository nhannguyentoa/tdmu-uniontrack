<?php

namespace Database\Seeders;

use App\Models\ActivityPlan;
use App\Models\Department;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityPlanSeeder extends Seeder
{
    /**
     * Kế hoạch hoạt động dự kiến năm học 2026-2027, trích từ file
     * "HOẠT ĐỘNG CĐ 26-27.docx" của Công đoàn Trường ĐHTDM.
     */
    public function run(): void
    {
        $academicYear = '2026-2027';
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        $departmentCodeByLabel = [
            'Ban TĐ-KT' => 'TTTD',
            'Ban Thi đua' => 'TTTD',
            'Ban Nữ công' => 'NCDS',
            'Ban Hành chính Hậu cần' => 'HCHC',
            'BTV, Ban Hành chính Hậu cần' => 'HCHC',
            'Ban Phong trào' => 'PT',
            'Ban Truyền thông' => 'TT',
        ];

        $groupCodeByLabel = [
            'Tổ CĐ1' => 'CD1',
            'Tổ CĐ4' => 'CD4',
            'Ngoại ngữ' => 'KNN',
            'Khoa CNVH' => 'KCNVH',
            'Trường Luật và QL' => 'TLQL',
        ];

        $rows = [
            [8, 'Xét thi đua năm học 2025-2026', null, 'Ban TĐ-KT', null],
            [8, 'Sinh hoạt, bồi dưỡng chính trị, chuyên môn đầu năm học', null, null, null],
            [8, 'Khen thưởng con CBVC, NLĐ có thành tích xuất sắc năm học 2025-2026', null, 'Ban Nữ công', null],
            [9, 'Tổ chức khám sức khỏe cho viên chức, người lao động', 'Tổ CĐ1', 'Ban Hành chính Hậu cần', null],
            [9, 'Tổ chức Tết Trung thu', null, 'Ban Phong trào', null],
            [9, 'Tuyên truyền kỷ niệm Quốc khánh 2/9', null, 'Ban Truyền thông', null],
            [10, 'Tặng quà cho người cao tuổi là cha mẹ viên chức, người lao động', null, 'Ban Nữ công', null],
            [10, 'Tổ chức hoạt động 20/10: Nấu món tráng miệng cho gia đình 4-5 người', 'Ngoại ngữ', 'Ban Phong trào', null],
            [10, 'Họp Ban Chấp hành: rà soát kiện toàn nhân sự, rà soát quy chế thu chi', null, 'BTV, Ban Hành chính Hậu cần', null],
            [11, 'Cuộc thi hài kịch', 'Khoa CNVH', 'Ban Phong trào', null],
            [11, 'Tổ chức kỷ niệm Ngày Nhà giáo Việt Nam 20/11', null, 'Ban Hành chính Hậu cần', 'Các tổ tự tổ chức'],
            [11, 'Tuyên truyền pháp luật, tuyên truyền nghị quyết đại hội Công đoàn', 'Trường Luật và QL', null, null],
            [12, 'Bình xét "Giỏi việc nước, Đảm việc nhà"', null, 'Ban Nữ công', null],
            [12, 'Tặng quà kỷ niệm 22/12', null, 'Ban Hành chính Hậu cần', null],
            [12, 'Tổ chức hội nghị viên chức, người lao động', null, null, 'BTV'],
            [1, 'Triển khai thu kinh phí thực hiện công trình đại hội (lần 2)', null, 'Ban Hành chính Hậu cần', null],
            [1, 'Tổ chức Phiên chợ Tết', null, 'Ban Phong trào', 'Đăng cai: khối Trường Kinh tế'],
            [2, 'Tuyên truyền kỷ niệm ngày thành lập Đảng 3/2', null, 'Ban Truyền thông', null],
            [2, 'Chăm lo Tết cho viên chức, người lao động', null, 'Ban Hành chính Hậu cần', null],
            [3, 'Tổ chức hoạt động kỷ niệm Quốc tế Phụ nữ 8/3', null, 'Ban Nữ công', null],
            [3, 'Gặp gỡ tặng quà cho Đoàn Thanh niên nhân 26/3', null, 'Ban Phong trào', null],
            [4, 'Tuyên truyền kỷ niệm 30/4 và 1/5', null, 'Ban Truyền thông', null],
            [4, 'Tổ chức cuộc thi văn hóa đọc (bảng giảng viên)', 'Tổ CĐ4', null, 'Ban Thư viện chủ trì'],
            [5, 'Quyên góp sách truyện tặng con em khu nhà trọ công nhân phường Phú Lợi', null, 'Ban Hành chính Hậu cần', null],
            [6, 'Tổ chức 1/6, khen thưởng con viên chức, người lao động có thành tích học tập xuất sắc', null, 'Ban Nữ công', null],
            [6, 'Hội thao kỷ niệm thành lập Trường', 'Khoa CNVH', 'Ban Phong trào', null],
            [7, 'Thăm hỏi tặng quà 27/7', null, 'Ban Hành chính Hậu cần', null],
            [7, 'Xét thi đua năm học 2026-2027', null, 'Ban Thi đua', null],
            [7, 'Tổ chức du lịch nghỉ dưỡng năm học', null, 'Ban Hành chính Hậu cần', '4 đợt, 1 địa điểm'],
        ];

        foreach ($rows as [$month, $title, $groupLabel, $deptLabel, $note]) {
            $hostUnionGroupId = $groupLabel
                ? UnionGroup::where('code', $groupCodeByLabel[$groupLabel] ?? '__none__')->value('id')
                : null;

            $departmentId = $deptLabel
                ? Department::where('code', $departmentCodeByLabel[$deptLabel] ?? '__none__')->value('id')
                : null;

            $noteParts = array_filter([$groupLabel && ! $hostUnionGroupId ? "Đơn vị đăng cai (chưa liên kết hệ thống): {$groupLabel}" : null, $note]);

            ActivityPlan::updateOrCreate(
                ['academic_year' => $academicYear, 'month' => $month, 'title' => $title],
                [
                    'host_union_group_id' => $hostUnionGroupId,
                    'department_id' => $departmentId,
                    'note' => $noteParts ? implode(' — ', $noteParts) : null,
                    'status' => ActivityPlan::STATUS_PLANNED,
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
