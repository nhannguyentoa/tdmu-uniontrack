<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Services\Ai\AiException;
use App\Services\Ai\AiManager;
use App\Services\Ai\DataTools;
use App\Services\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trợ lý hỏi đáp dữ liệu bằng tiếng Việt: AI gọi các công cụ tra cứu chỉ-đọc, phạm vi theo quyền người hỏi.
 */
class AiAssistantController extends Controller
{
    public function __construct(private readonly AiManager $ai, private readonly EvaluationService $evaluation)
    {
    }

    public function index(Request $request): View
    {
        $tools = new DataTools($request->user(), $this->evaluation);

        return view('ai.assistant', [
            'provider' => $this->ai->activeName(),
            'scope' => $tools->scopeDescription(),
            'examples' => [
                'Tổ nào chậm tiến độ tháng này?',
                'So sánh điểm thưởng các tổ năm học '.\App\Support\AcademicYear::forDate(now()),
                'So sánh tỷ lệ hoàn thành hoạt động giữa các tổ',
                'Thống kê lượt tham gia hoạt động của các tổ',
                'Kế hoạch nào đang quá hạn?',
            ],
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'history' => ['nullable', 'array', 'max:8'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.text' => ['required_with:history', 'string', 'max:3000'],
        ], [
            'question.required' => 'Vui lòng nhập câu hỏi.',
            'question.max' => 'Câu hỏi quá dài (tối đa 500 ký tự).',
        ]);

        $user = $request->user();
        $tools = new DataTools($user, $this->evaluation);
        $provider = $this->ai->provider();
        $started = microtime(true);

        try {
            $result = $provider->chat($data['question'], array_values($data['history'] ?? []), $tools);
        } catch (AiException $e) {
            $this->log($user->id, $provider->name(), $provider->model(), $data['question'], false, $started, null, null, [], $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 422);
        }

        $this->log($user->id, $result->provider, $result->model, $data['question'], true, $started, $result->inputTokens, $result->outputTokens, $result->toolCalls);

        return response()->json([
            'answer' => $result->answer,
            'tools' => array_column($result->toolCalls, 'name'),
            'provider' => $result->provider,
            'simulated' => $provider->isSimulated(),
        ]);
    }

    /**
     * @param  list<array{name: string, args: array<string, mixed>}>  $calls
     */
    private function log(int $userId, string $provider, string $model, string $question, bool $success, float $started, ?int $in, ?int $out, array $calls, ?string $error = null): void
    {
        AiUsageLog::create([
            'feature' => AiUsageLog::FEATURE_QA,
            'provider' => $provider,
            'model' => $model,
            'user_id' => $userId,
            'subject' => mb_substr($question, 0, 60),
            'success' => $success,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'input_tokens' => $in,
            'output_tokens' => $out,
            'stats' => ['tools' => array_column($calls, 'name')],
        ]);
    }
}
