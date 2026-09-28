<?php

return [
    /*
     * Nhà cung cấp AI cho trợ lý thẩm định thi đua: mock (giả lập, không tốn phí), gemini, claude.
     */
    'provider' => env('AI_PROVIDER', 'mock'),

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'claude' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('CLAUDE_MODEL', 'claude-opus-5'),
    ],

    // Giới hạn tệp minh chứng đính kèm cho mỗi lần gọi AI (kiểm soát chi phí và thời gian).
    'max_files' => (int) env('AI_MAX_FILES', 6),
    'max_file_bytes' => (int) env('AI_MAX_FILE_BYTES', 4 * 1024 * 1024),

    // Số hoạt động tối đa đưa vào ngữ cảnh mỗi lần (mới nhất trước).
    'max_activities' => (int) env('AI_MAX_ACTIVITIES', 40),

    'timeout' => (int) env('AI_TIMEOUT', 90),
];
