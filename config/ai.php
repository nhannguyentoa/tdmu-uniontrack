<?php

return [
    /*
     * Nhà cung cấp AI cho trợ lý nhập điểm danh từ ảnh và trợ lý soạn văn bản:
     * mock (giả lập, không gọi mạng, không tốn phí) hoặc gemini.
     */
    'provider' => env('AI_PROVIDER', 'mock'),

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    // Dung lượng tối đa của ảnh/PDF danh sách tham gia (KB).
    'max_upload_kb' => (int) env('AI_MAX_UPLOAD_KB', 5120),

    // Ngưỡng độ giống tên (0-1) để tự chọn đoàn viên khi AI đọc sai chính tả.
    'fuzzy_threshold' => (float) env('AI_FUZZY_THRESHOLD', 0.85),

    'timeout' => (int) env('AI_TIMEOUT', 90),
];
