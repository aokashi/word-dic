<!DOCTYPE HTML>
<title>単語辞書</title>
<meta charset="UTF-8">
<style>
body, input, select, textarea{
	font-family: "ヒラギノ角ゴ ProN W3", "Hiragino Kaku Gothic ProN", "メイリオ", "Meiryo", -sans-serif;
}

body{
	background-color: #C0FFC0;
	color: #000;
	margin: 0;
}

.content{
	margin: 0 32px;
	margin: 0 2rem;
}

#header-wrap{
	background-color: #008000;
	color: #FFF;
	margin-bottom: 16px;
	margin-bottom: 1rem;
	padding: 16px 0;
	padding: 1rem 0;
}

#main-content{
	background-color: #FFF;
	border: 1px solid #00C000;
	color: #000;
	margin: 16px;
	margin: 1rem;
	padding: 16px;
	padding: 1rem;
}

#main-content dt{
	font-weight: bold;
	border-bottom: 1px dashed #008000;
}

#main-content h2,
#main-content h3{
	padding: .125em;
}

#main-content h2{
	font-size: 28px;
	font-size: 1.75rem;
	background-color: #008000;
	color: #FFF;
}

#main-content h3{
	font-size: 24px;
	font-size: 1.5rem;
	border-bottom: 2px dashed #008000;
}

#main-content hr{
	border: 1px solid #008000;
}

#main-content div{
	margin: 16px 0;
	margin: 1rem 0;
}
</style>
<div id="header-wrap">
	<header id="header-content" class="content">
		<h1>単語辞書</h1>
	</header>
</div>
<div id="main-content" class="content">
<?php
/*
	-----
	PHPここから開始
	-----
*/
/*
	○define
*/
// リストファイル
define("FILE_CSV", "list.csv");
// リストファイル(テンポラリ)
define("FILE_TMP", "list_tmp.csv");
// 先頭行を飛ばすか
define("SKIP_HEAD", TRUE);
// 「全て表示」をした際に表示される単語の数
define("DISP_NUM", 10);
/*
	○クラス「単語」
*/
class word{
	// 品詞の補助
	public $type_sub;
	// 品詞
	public $type;
	// 発祥
	public $origin;
	// 名前
	public $name;
	// 呼び方
	public $name_easy;
	// 説明
	public $description;
	// 初期化(コンストラクタ)
	function __construct($type_sub = NULL, $type = NULL, $origin = NULL, $name = NULL, $name_easy = NULL, $description = NULL){
		$this->type_sub = $type_sub;
		$this->type = $type;
		$this->origin = $origin;
		$this->name = $name;
		$this->name_easy = $name_easy;
		$this->description = $description;
	}
	/*
		■読み込み
			ファイルから単語を読み込む
			ファイルポインタは別途用意
			文字列そのままを読み込みたい場合はrecord_read関数を使う
	*/
	function read($fp){
		// 先頭行である場合
		if(ftell($fp) <= 0){
			// 先頭行を飛ばす場合、1行読んで飛ばす
			if(SKIP_HEAD) fgets($fp);
		}
		// レコードを読み込む
		$record = fgets($fp);
		if($record === false){
			// 末尾である場合はfalseを返す
			return false;
		}else{
			// explodeでカンマごとに文字列を分けてtrueを返す
			list($this->type_sub, $this->type, $this->origin, $this->name, $this->name_easy, $this->description) = explode(',', $record);
			return true;
		}
	}
	/*
		■表示
			dlタグを忘れないように
	*/
	function disp(){
		$str_thisfile = basename(__FILE__);
		if($this->name === NULL || $this->description === NULL) return; // 空の単語だった場合はすぐに終了する
		echo "<dt>";
		// 名前
		echo $this->name;
		if($this->name_easy != NULL) echo "({$this->name_easy})";
		if($this->type != NULL || $this->origin != NULL){
			echo ":";
			// 品詞
			if($this->type != NULL){
				echo $this->type;
				if($this->type_sub != NULL) echo "({$this->type_sub})";
			}
			// 発祥
			if($this->origin != NULL) echo " 発祥:{$this->origin}";
		}
		echo "</dt>\n";
		// 説明
		$disp_description = str_replace("\\n", "</dd><dd>", $this->description); // 改行を変換
		echo "<dd>" . $disp_description . "</dd>\n";
	}
}
// クラスはここまで

/*
	○システム関数(内部処理)
*/
	/*
		■レコード自体をそのまま読み込む関数
	*/
	function record_read($fp){
		// 先頭行である場合
		if(ftell($fp) <= 0){
			// 先頭行を飛ばす場合、1行読んで飛ばす
			if(SKIP_HEAD) fgets($fp);
		}
		// 読み込んだレコードを直接返す
		return fgets($fp);
	}

	/*
		■文字列の配列から配列を参照し、セットされているかチェックする関数
	*/
	function isset_valuename($arr, $names){
		// 配列でなかったり、配列の中が何もなかった場合はfalseを返す
		if(!is_array($arr) || !is_array($names) || count($arr) == 0) return false;
		// $resultを定義
		$result = true;
		// foreach文で各値を確認する
		foreach($names as $name){
			// 確認した値がセットされていない場合は
			if(!isset($arr[$name])){
				// $resultをfalseにし、for文から抜ける
				$result = false;
				break;
			}
		}
		// $resultを返す
		return $result;
	}

	/*
		■検索(見つかった位置の配列を返す)
		$str : (String)検索対象
		$word : (String)検索単語 検索できる単語は一つのみ
	*/
	function search($str, $word){
		/*
			$found_pos : (int)$strの中で$wordが見つかった際に出力される配列
			$strpos_from : (int)strposで探す開始値
			$strpos_buf : (int)strposで最初に入れる値 その値によって$found_posに代入されるか決まる
			$i, $j : (int)for文の一時変数
		*/
		// $found_posを作る
		$found_count = array();
		// $strpos_fromを定義
		$strpos_from = 0;
		// とりあえず$strpos_bufに代入して、末尾であるか確認する
		for($i = 0; ($strpos_buf = strpos($str, $word, $strpos_from)) !== false; $i++){
			// 末尾でない(falseでない)場合は代入した$strpos_bufを$found_posに代入する
			$found_pos[$i] = $strpos_buf;
			// $strpos_fromを更新する
			// strlen文足しているのは永遠に求められなくなる不具合を防ぐため
			$strpos_from = $found_pos[$i] + strlen($word);
			// $found_countをカウント
			$found_count++;
		}
		// 検索した単語が何も見つからない場合はfalseを返す
		if($i == 0) return false;
		else        return $found_pos;
	}

	/*
		■対象文字列を指定位置から指定した長さまで指定文字列を囲む
		$target : (String)対象文字列
		$offset : (int)囲む位置(配列により、複数囲むことが可能) falseの場合は開始早々元の文字列($target)を返される
		$length : (int)囲む範囲($offsetから$length分を囲む)
		$parts_before : (String)囲む文字列
		$parts_after = null : (String)後ろ部分に囲む文字列 指定することで前と後、それぞれ別にできる 設定した場合はwrap_check関数が実行される
	*/
	function str_wrap($target, $offset, $length, $parts_before, $parts_after = null){
		/*
			●無名関数 wrap_check
			str_wrapで囲んでよいか確認する
			$str : 対象文字列
			$parts_before : 検索する文字列 これが$strの中に含んでいるとfalseを返される
			$parts_after : 検索する文字列二つ目 
			・囲んでよい条件
	 		　検索する文字列二つ分を対象文字列の中から検索し、最後に見つけた文字列が一つ目($parts_before)でないこと
			　上の条件のうち一つでも満たすことで囲んでよいことになる
		*/
		$wrap_check = function(&$str, &$parts_before, &$parts_after){
			// まず検索をしてみる
			$search_result_before = search($str, $parts_before);
			$search_result_after = search($str, $parts_after);
			// それぞれ検索した結果で比較をする
			if($search_result_before == false && $search_result_after == false){
				// 両方ともない場合はtrueを返す
				return true;
			}else if($search_result_before == false){
				// 最初のif文の条件から読み取ると $search_result_before と $search_result_after うち一つだけ結果があるとわかる
				// ここでは$search_result_afterのみに結果がある場合となり、閉じの後だとわかる。trueを返す
				return true;
			}else if($search_result_after == false){
				// 次に$search_result_beforeのみに結果がある場合。ここでは閉じの中だとわかり、falseを返す
				return false;
			}else{
				// 最後残るのは両方とも結果を持っているとわかる。その際はそれぞれ配列の最後の値を比較する
				// $search_result_beforeのほうが多い場合は閉じの中だとわかり、falseを返す。他はtrueを返す
				if($search_result_before[count($search_result_before) - 1] > $search_result_after[count($search_result_after) - 1])
					return false;
				else
					return true;
			}
		};
		/*
			$str_result : 挿入済み文字列 この文字列が返される
			$i : for文の一時変数
			$substr_length : 次の検索位置までの距離
		*/
		// $offsetがfalseである場合
		if($offset == false){
			// 開始早々元の文字列を返す(囲むための材料が不足しているため)
			return $target;
		}else if(is_array($offset)){
			// $offsetが配列である場合
			// 代入を容易にするため、ソートをする
			sort($offset);
		}else{
			// 配列でなくても配列にしておく
			$offset = array($offset);
		}
		// substrで囲む文字列まで抜き出し、$str_resultに代入する
		// for文の中では$str_insertを経由するが今回は代入しか行わないため$str_insertを経由せず$wrap_checkで確認する
		$str_result = substr($target, 0, $offset[0]);
		// $parts_afterが設定されている場合
		if($parts_after != null){
			// wrap_checkで囲むべきかチェックする
			$wrap_flg = $wrap_check($str_result, $parts_before, $parts_after);
		}else{
			// 設定されていない場合は常に$wrap_flgはtrueになる
			$wrap_flg = true;
		}
		// for文
		for($i = 0; $i < count($offset); $i++){
			// 抜き出した文字列の中に囲む部分が含んでいるか
			if($wrap_flg){
				// 囲む文字列を$str_resultに加える
				$str_result .= $parts_before;
				// 囲まれる部分も追加する
				$str_result .= substr($target, $offset[$i], $length);
				// $parts_afterが設定されている場合は$parts_afterを入れる そうでない場合は$parts_beforeを入れる
				if($parts_after != null) $str_result .= $parts_after;
				else                     $str_result .= $parts_before;
			}else{
				// 囲む部分が含んでいる場合は囲まれる部分のみ追加する
				$str_result .= substr($target, $offset[$i], $length);
			}
			// 囲む文字列の後の部分も忘れずに行う
			// $iの位置により抜き出す先が異なる
			// 抜き出す長さ分は...次の検索位置 - (今の検索位置 + 長さ)...という仕組み 長さを引かないと囲まれる部分も含まれて抜き出されるから
			if($i + 1 < count($offset)){
				// $str_insertに代入 $str_insertを経由して$str_resultに追加される
				$str_insert = substr($target, $offset[$i] + $length, $offset[$i + 1] - ($offset[$i] + $length));
				$str_result .= $str_insert;
				// $parts_afterが設定されている場合
				// elseから先は設定済みであるため用意していない
				if($parts_after != null){
					// $str_insertの中に囲む文字列が含んでいるか確認する
					$wrap_flg = $wrap_check($str_insert, $parts_before, $parts_after);
				}
			}else{
				// もう末尾であるため$str_insertを経由せず直接$str_resultに追加する
				$str_result .= substr($target, $offset[$i] + $length);
			}
		}
		
		// 囲んだ文字列が返される
		return $str_result;
	}

	/*
		■strlen関数を使って文字列配列を文字列の長さ順にソートする
	*/
	function sort_strlen($arr){
		// 配列でない場合はそのままお返しとなります
		if(!is_array($arr)) return $arr;
		// ソート後の配列を宣言
		$sort_arr = $arr;
		for($i = 0; $i < count($sort_arr); $i++){
			for($j = $i + 1; $j < count($sort_arr); $j++){
				// 前者より後者のほうが長かった場合
				if(strlen($sort_arr[$i]) < strlen($sort_arr[$j])){
					// いつもの交換法
					$buf = $sort_arr[$i];
					$sort_arr[$i] = $sort_arr[$j];
					$sort_arr[$j] = $buf;
				}
			}
		}
		// $bufを消す
		unset($buf);
		// ソートした配列を返す
		return $sort_arr;
	}
	
/*
	○システム関数(表示)
*/
	/*
		■タイトル部分を表示するどうでもいい関数
	*/
	function disp_title($str){
		echo "<h2>{$str}</h2>\n";
	}
	
	/*
		■戻るボタンを表示
	*/
	function disp_back($str_for = NULL, $str_name = "戻る"){
		$str_thisfile = basename(__FILE__);
		echo "<div>";
		echo "<form action=\"$str_thisfile\" method=\"GET\">";
		// $str_forが記述されている場合はinputタグをもう一つ追加する また、disp_menuを直接記述した場合押したときにエラーが起きました
		if($str_for !== NULL) echo "<input type=\"hidden\" name=\"mode\" value=\"". $str_for . "\">";
		echo "<input type=\"submit\" value=\"" . $str_name . "\">";
		echo "</form>";
		echo "</div>";
	}

/*
	○[GET]検索(表示)
*/
function disp_search(){
	$str_thisfile = basename(__FILE__);
	disp_title("検索");
	echo <<< EOE
<p>ここでは詳細な検索ができます
<form action="{$str_thisfile}" method="GET">
<input type="hidden" name="mode" value="exe_search_detail">
<div>名前とその読み、説明はその文字列が含んでいるか確認をします</div>
<table>
<tr><td><label for="input-name">名前</label></td><td><input id="input-name" type="text" name="input-name"></td></tr>
<tr><td><label for="input-name_easy">名前(呼び)</label></td><td><input id="input-name_easy" type="text" name="input-name_easy"></td></tr>
<tr><td><label for="input-description">説明(複数指定可)</label></td><td><input id="input-description" type="text" name="input-description"></td></tr>
</table>
<h3>絞り込み</h3>
<div>以下は両方とも一致しているか確認をします</div>
<table>
<tr><td><label for="input-origin">発祥地</label></td><td><input id="input-origin" type="text" name="input-origin"></tr>
<tr><td><label for="input-type">品詞</label></td><td><input id="input-type" type="text" name="input-type"></tr>
<tr><td><label for="input-type_sub">品詞(補助)</label></td><td><input id="input-type_sub" type="text" name="input-type_sub"></tr>
</table>
<div>上記項目が<strong>全て</strong>一致することでリストに載ります(空欄を記述した場合は例外です)</div>
<div><input type="submit" value="検索"></div>
</form>
EOE;
	disp_back();
}

/*
	○[GET]簡易検索(実行)
	$source_word 入力した文字列 XSS脆弱性対策のためhtmlspecialchars変数を経由する
	$arr_word    検索単語をスペース区切りに分けたもの
	$word        クラス 読み込んだ単語の情報が格納されている
	$count       見つけた数 0になると見つからないとメッセージが表示される
	$c           見つけた単語の種類の数 この変数を用いることで複数のキーワードを検索した際、キーワードすべてが入っている単語のみ表示することができる
	$str_word    forarch文で使う $arr_wordを単語ごとに焦点をあてたもの
	$result      配列 'name'は名前の結果 'description'は説明の結果 説明だけは検索した部分をマーキングするため細かく行う
*/
function exe_search_simple(){
	disp_title("検索結果"); // タイトル部分
	
	if(!isset_valuename($_GET, array("mode", "input-word"))) die("<p>想定外のエラーが発生しました\n"); // 正常なルートでアクセスできているか確認
	if(empty($_GET['input-word'])) die("<p>記入漏れがあります\n"); // 検索単語が空でないか確認
	
	$source_word = htmlspecialchars($_GET['input-word'], ENT_QUOTES); // XSS脆弱性対策のためhtmlspecialcharsで違反なタグを防ぐ
	$arr_word = explode(' ', $source_word); // 各検索単語を区切る
	$arr_word = sort_strlen($arr_word); // 各検索単語を長さ順にソートする
	
	$file_csv = @fopen(FILE_CSV, "r") or die("<p>ファイルエラーが発生しました\n"); // ファイルを開く
	$word = new word(); // $wordをインスタンス化
	
	echo "<p>キーワード " . $source_word . " を表示しています\n";
	echo "<hr>";
	
	$count = 0; // $countをリセット
	while($word->read($file_csv) !== FALSE){ // 読み込んだ単語が尽きるまで行う
		$c = 0; // $cをリセット
		foreach($arr_word as $str_word){ // 検索した単語ごとに作業
			$result['name'] = strpos($word->name, $str_word); // 検索してみる
			$result['description'] = search($word->description, $str_word);
			
			if($result['name'] !== FALSE || $result['description'] !== FALSE){ // 検索した結果がfalseでない場合
				$c++; // $cを足す
				if($result['description'] !== FALSE) $word->description = str_wrap($word->description, $result['description'], strlen($str_word), "<mark>", "</mark>"); // 説明の部分に目的の単語が見つかった場合検索した単語をマーキングする
			}
		}
		if($c == count($arr_word)){
			if($count == 0) echo "<dl>\n"; // dlタグは検索結果が見つかった時だけに表示したい 結果が初めて見つかった時だけdlタグを付け始める
			$word->disp(); // 結果が見つかったら表示する
			$count++;
		}
	}
	
	fclose($file_csv); // ファイルを閉じる
	
	if($count == 0) echo "<p>検索した結果が見つかりませんでした\n"; // 見つからない場合はメッセージを表示
	else echo "</dl>\n"; // 閉じタグを忘れないようにする
	
	disp_back(); // 戻るボタンを表示
}

/*
	○[GET]詳細検索(実行)
	基本的に簡易検索と大きな変化はない 簡易検索と関数を統合してもよいがソースコードが見づらく、スパゲティコードになること間違いなし
	簡易検索でも書かれているコメントは省略している
	$source            $_GETをXSS脆弱性対策のためにhtmlspecialchars変数を経由した配列
	$arr_check_pattern 検索条件の表示において値を間接的に参照するうえで必要な配列
	$arr_title_pattern 検索条件の表示において検索内容のタイトル部分を間接的に参照するうえで必要な配列
	$j                 単語の比較において一致している分類の数
*/
function exe_search_detail(){
	disp_title("検索結果");
	
	if(!isset_valuename($_GET, array("mode", "input-name", "input-name_easy", "input-description", "input-origin", "input-type", "input-type_sub"))) die("<p>想定外のエラーが発生しました\n");
	if(empty($_GET['input-name']) && empty($_GET['input-name_easy']) && empty($_GET['input-description']) && empty($_GET['input-origin']) && empty($_GET['input-type']) && empty($_GET['input-type_sub'])) die("<p>記入漏れがあります\n");
	
	foreach($_GET as $key => $str){ // XSS脆弱性対策のためhtmlspecialcharsで違反なタグを防ぐ
		$source[$key] = htmlspecialchars($str, ENT_QUOTES);
	}
	
	$arr_word = explode(' ', $_GET['input-description']);
	$arr_word = sort_strlen($arr_word);
	
	$file_csv = @fopen(FILE_CSV, "r") or die("<p>ファイルエラーが発生しました\n");
	$word = new word();
	
	echo "<h3>検索条件</h3>\n"; // 検索条件を表示する
	$arr_check_pattern = array('input-name', 'input-name_easy', 'input-description', 'input-origin', 'input-type', 'input-type_sub'); // $arr_check_patternを用意
	$arr_title_pattern = array("名前", "名前(呼び)", "説明", "発祥", "品詞", "品詞(補助部分)");
	echo "<dl>\n";
	for($i = 0; $i < 6; $i++){
		if(!empty($source[$arr_check_pattern[$i]])) echo "<dt>" . $arr_title_pattern[$i] . "</dt><dd>" . $source[$arr_check_pattern[$i]] . "</dd>\n";
	}
	echo "</dl>\n";
	echo "<hr>\n";
	
	$count = 0;
	while($word->read($file_csv) !== FALSE){
		$j = 0; // $jをリセット
		
		// ここから先は各種類ごとに確認をする for文で回してもよいがわかりづらくなるため設けないようにしている
		if(!empty($source['input-origin'])){ // 指定した値が空でないか確認(ここでは発祥)
			if($word->origin === $source['input-origin']) $j++;
		}else{ // 空である場合は無条件で$jを足す(条件として含んでいないため)
			$j++;
		}
		if(!empty($source['input-type'])){ // 品詞
			if($word->type === $source['input-type']) $j++;
		}else{
			$j++;
		}
		if(!empty($source['input-type_sub'])){ // 品詞(補助)
			if($word->type_sub === $source['input-type_sub']) $j++;
		}else{
			$j++;
		}
		if(!empty($source['input-name'])){ // 名前
			if(strpos($word->name, $source['input-name']) !== FALSE) $j++;
		}else{
			$j++;
		}
		if(!empty($source['input-name_easy'])){ // 名前(読み)
			if(strpos($word->name_easy, $source['input-name_easy']) !== FALSE) $j++;
		}else{
			$j++;
		}
		if(!empty($source['input-description'])){
			$c = 0; // $cをリセット
			foreach($arr_word as $str_word){ // 説明
				if(($result = search($word->description, $str_word)) !== FALSE){
					$c++;
					$word->description = str_wrap($word->description, $result, strlen($str_word), "<mark>", "</mark>");
				}
			}
			if($c == count($arr_word)) $j++;
		}else{
			$j++;
		}
		if($j == 6){
			if($count == 0) echo "<dl>\n";
			$word->disp(); // $jが6まで達したら表示する
			$count++;
		}
	}
	
	fclose($file_csv);
	
	if($count == 0) echo "<p>検索した結果が見つかりませんでした\n";
	else echo "</dl>\n";
	
	disp_back();
}

/*
	○[GET]すべて表示
*/
function disp_all(){
	/*
		あらかじめ
		仕様上、番号は0からではなく1から始まっている
	*/
	$str_thisfile = basename(__FILE__);
	
	// 単語の数を確認する無名関数
	$words = function(){
		$file_csv = @fopen(FILE_CSV, "r") or die("<p>ファイルエラーが発生しました\n");
		for($words = 0; record_read($file_csv) !== FALSE; $words++);
		fclose($file_csv);
		
		return $words;
	};
	
	// タイトル部分
	disp_title("すべて表示");
	// dlタグを用意
	echo "<dl>\n";
	
	// 単語の数を用意
	$words = $words();
	
	// GETのnumが定義されている場合は整数に変えて$numへ代入
	// そうでない場合は$numを1にリセット
	if(isset($_GET['num'])) $num = intval($_GET['num']);
	else $num = 1;
	
	// ナビゲーションを表示
	echo "<div>\n";
	
	// 最初を表示
	if($num > 2) echo "\t<form action=\"" . $str_thisfile . "\" method=\"GET\" style=\"display: inline;\"><input type=\"hidden\" name=\"mode\" value=\"disp_all\"><input type=\"hidden\" name=\"num\" value=\"1\"><input type=\"submit\" value=\"最初\">\n</form>\n";
	else         echo "\t<input type=\"submit\" value=\"最初\" disabled>\n";
	// 最初以外の場合、前を表示 押しやすさ優先のため使えない場合は無効にし、一番上に移動する
	if($num > 1) echo "\t<form action=\"" . $str_thisfile . "\" method=\"GET\" style=\"display: inline;\"><input type=\"hidden\" name=\"mode\" value=\"disp_all\"><input type=\"hidden\" name=\"num\" value=\"" . ($num - 1) . "\"><input type=\"submit\" value=\"前\">\n</form>\n";
	else         echo "\t<input type=\"submit\" value=\"前\" disabled>\n";
	// 最大ページ数を定義 $max_numは実数であるため整数に変える
	$max_num = (int)ceil($words / DISP_NUM);
	// 現在の位置を表示
	echo "\t" . $num . "/" . $max_num . "\n";
	// 末尾以外の場合、次を表示 $max_numは実数であるためintへキャスト
	if($num < $max_num) echo "\t<form action=\"" . $str_thisfile . "\" method=\"GET\" style=\"display: inline;\"><input type=\"hidden\" name=\"mode\" value=\"disp_all\"><input type=\"hidden\" name=\"num\" value=\"" . ($num + 1) . "\"><input type=\"submit\" value=\"次\">\n</form>\n";
	else                echo "\t<input type=\"submit\" value=\"次\" disabled>\n";
	if($num < $max_num - 1) echo "\t<form action=\"" . $str_thisfile . "\" method=\"GET\" style=\"display: inline;\"><input type=\"hidden\" name=\"mode\" value=\"disp_all\"><input type=\"hidden\" name=\"num\" value=\"" . $max_num . "\"><input type=\"submit\" value=\"最後\">\n</form>\n";
	else                 echo "\t<input type=\"submit\" value=\"最後\" disabled>\n";
	echo "</div>\n";
	
	// ファイルを開く
	$file_csv = @fopen(FILE_CSV, "r") or die("<p>ファイルエラーが発生しました\n");
	// クラスを追加
	$word = new word();
	// 表示したい範囲までファイルポインタの位置を移動させる
	// $numは最小値が1であるため1を引いて0の時にも対応させる
	for($i = 0; $i < ($num - 1) * DISP_NUM; $i++) $word->read($file_csv);
	// $iをリセット 読み込み、表示
	for($i = 1; $word->read($file_csv) !== FALSE && $i <= DISP_NUM; $i++){
		$word->disp();
		echo "\n";
	}
	// ファイルを閉じる
	fclose($file_csv);
	
	// 閉じタグ
	echo "</dl>\n";
	
	/*// 情報を表示(別のところに移したいな)
	echo "<h3>情報</h3>\n";
	echo "<ul><li>単語の数..." . $words . "</li></ul>\n";*/
	
	// 戻るボタンを表示
	disp_back();
}

/*
	○メニュー(表示)
*/
function disp_menu(){
	$str_thisfile = basename(__FILE__);
	echo "<h2>メニュー</h2>\n";
	echo "<div><form action=\"" . $str_thisfile . "\" method=\"GET\"><input type=\"hidden\" name=\"mode\" value=\"disp_search\"><input id=\"input-search\" type=\"submit\" value=\"詳細検索\"></form></div>\n";
	echo "<div><form action=\"" . $str_thisfile . "\" method=\"GET\"><input type=\"hidden\" name=\"mode\" value=\"disp_all\"><input id=\"input-all\" type=\"submit\" value=\"すべて表示\"></form></div>\n";
	echo <<< EOE
<h3>クイック検索</h3>
<form action="{$str_thisfile}" method="GET">
<input type="hidden" name="mode" value="exe_search_simple">
<div><input id="input-word" type="text" name="input-word"> <input type="submit" value="検索"></div>
<div><p>探す範囲は主に名前と説明です。範囲内に目的の単語が見つかった場合、リストに載ります<p>複数のキーワードを検索することが可能です その際、すべてのキーワードが含んでいる単語のみ表示します</div>
</form>
EOE;
}

// 初期化
clearstatcache();

// GETかPOSTで得た値に従って表示
if(isset($_GET['mode'])){
	$mode = $_GET['mode'];
	$mode();
}else if(isset($_POST['mode'])){
	$mode = $_POST['mode'];
	$mode();
}else{
	disp_menu();
}
/*
-----
PHPここまで
-----
*/
?>
</div>
