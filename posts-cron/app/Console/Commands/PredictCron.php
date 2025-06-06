<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use Illuminate\Console\Command;
use Carbon\Carbon;

class PredictCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:predict-cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = Carbon::tomorrow()->format('Ymd');

        echo "PredictCron command started for date: $date\n";

        echo json_encode($this->getWM($date));
    }

    private function getWM($date)
    {
        $url = env('WM_SOURCE') . "/?id=$date&tzo=0";
        echo $url . "\n";
        $response = Controller::getData($url);
        $data = json_decode($response);
        echo "Response: $response\n";
        if (!$data || !isset($data->stages) || !isset($data->matches)) {
            // Trả về mảng rỗng nếu JSON không hợp lệ hoặc thiếu dữ liệu cần thiết
            return [];
        }

        // 2. Tạo một mảng tra cứu cho 'stages' với key là 'id'
        // Điều này giúp tra cứu thông tin stage cho mỗi trận đấu nhanh hơn (O(1) thay vì O(n))
        $stagesById = [];
        foreach ($data->stages as $stage) {
            $stagesById[$stage->id] = $stage;
        }

        $formattedList = [];

        // 3. Lặp qua từng trận đấu trong mảng 'matches'
        foreach ($data->matches as $match) {
            // Lấy thông tin stage từ mảng tra cứu đã tạo
            // Sử dụng toán tử null coalescing (??) để cung cấp giá trị mặc định nếu không tìm thấy
            $stageInfo = $stagesById[$match->stageId] ?? null;

            $regionName = $stageInfo->regionName ?? 'Unknown Region';
            $stageName = $stageInfo->name ?? 'Unknown Stage';

            // Lấy các thông tin khác từ trận đấu
            $matchId = $match->id;
            $homeName = $match->homeName;
            $awayName = $match->awayName;

            // 4. Sử dụng Carbon để parse và định dạng ngày giờ UTC
            // Định dạng: Giờ:Phút Ngày/Tháng/Năm (ví dụ: 16:30 06/06/2025)
            $formattedTime = Carbon::parse($match->startTimeUtc)->format('H:i d/m/Y');

            // 5. Xây dựng chuỗi cuối cùng theo định dạng yêu cầu
            $formattedString = "matchId: {$matchId}: {$regionName} {$stageName} - {$homeName} vs {$awayName} - {$formattedTime}";

            // Thêm chuỗi đã định dạng vào mảng kết quả
            $formattedList[] = $formattedString;
        }

        return $formattedList;
    }
}
