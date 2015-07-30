<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
class libmecab
{   
  public static function mecab( $input , $mecab_bin)
  {
    $result = false;
    try {
      $descriptorspec = array(
        0 => array('pipe', 'r'),
        1 => array('pipe', 'w')
      );
      // mecabを実行
      $proc = proc_open( $mecab_bin."mecab", $descriptorspec, $pipes );
      if (is_resource($proc)) {
        // stdinへ書込
        fwrite( $pipes[0], $input );
        fclose( $pipes[0] );
        // stdoutを読込
        $tmp = stream_get_contents( $pipes[1] );
        fclose( $pipes[1] );
        // プロセスをクローズ
        proc_close($proc);
        // 結果を行にて分割
        $tmp = explode( "\n", $tmp );
        $result = array();
        // 各行を処理
        foreach($tmp as $line) {
          // パターンマッチさせ分割
          if (preg_match('/^([^\\s]+)\\s([^,]+),([^,]+),([^,]+),([^,]+),([^,]+),([^,]+),([^,]+),*([^,]+)*,*([^,]+)*$/', $line, $matches)) {
            // 日本語以外だと"読み"と"発音が設定されないので設定する
            for($ii = 1; $ii <= 10; $ii++) {
                if(!isset($matches[$ii])) {
                    $matches[$ii] = "*";
                }
            }
            // 戻り値に設定
            $result[] = array($matches[1] =>             // 表層形
                    array('hinshi' => $matches[2],     // 品詞
                        'hinshi1' => $matches[3],    // 品詞細分類１
                        'hinshi2' => $matches[4],    // 品詞細分類2
                        'hinshi3' => $matches[5],    // 品詞細分類3
                        'katuyoukei' => $matches[6], // 活用形
                        'katuyougata' => $matches[7],  // 活用型
                        'genkei' => $matches[8],   // 原型
                        'yomi' => $matches[9],     // 読み
                        'hatsuon' => $matches[10])); // 発音
          } 
        }
      }
    } catch (Exception $e) {
       
    }  
    return $result;
  }
}

?>