<?php
/**
 * $Id: Fileprocess.class.php 57059 2015-08-25 06:43:36Z tomohiro_ichikawa $
 *
 * プロセス制御を行う
 *
 * @author IVIS
 */
class Repository_Components_Util_Fileprocess
{
    /**
     * タイムアウト時間を指定してコマンドを実行する
     *
     * @param string $cmd コマンド
     * @param int $timeout タイムアウト時間 ミリ秒単位で指定する
     * @param int $interval 実行状態インターバル ミリ秒単位で指定する
     * @return mixed 完了時: int 終了コード / タイムアウトor起動失敗時: false
     */
    public static function exec($cmd, $timeout=10000, $interval=100)
    {
        // コマンド実行
        $process = proc_open(escapeshellcmd($cmd), array(), $pipes);
        if(!is_resource($process)){
            return false;
        }
        
        // タイムアウト判定用
        $cnt = 0;
        $retry = ($timeout / $interval) + 1;
        $running = true;
        $exitcode = 0;
        
        do{
            // 実行状態を取得するために $interval の時間ごとにsleep
            usleep($interval*1000);
            
            // 実行状態を取得
            $status  = proc_get_status($process);
            $running = $status['running'];
            $exitcode = $status['exitcode'];
            
            // カウンタインクリメント
            $cnt++;
        }
        while($running && $cnt < $retry);
        
        // タイムアウトの場合強制終了
        if ($running) {
            proc_terminate($process);
            proc_close($process);
            return false;
        }
        
        // プロセス終了
        proc_close($process);
        
        return $exitcode;
    }
}
?>
