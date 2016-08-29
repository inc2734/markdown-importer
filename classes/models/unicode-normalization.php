<?php
class Markdown_Importer_Unicode_Normalization {

	/**
	 * Target character
	 * @var array
	 */
	protected $targets = array(
		"か\xE3\x82\x99" => 'が',
		"き\xE3\x82\x99" => 'ぎ',
		"く\xE3\x82\x99" => 'ぐ',
		"け\xE3\x82\x99" => 'げ',
		"こ\xE3\x82\x99" => 'ご',
		"さ\xE3\x82\x99" => 'ざ',
		"し\xE3\x82\x99" => 'じ',
		"す\xE3\x82\x99" => 'ず',
		"せ\xE3\x82\x99" => 'ぜ',
		"そ\xE3\x82\x99" => 'ぞ',
		"た\xE3\x82\x99" => 'だ',
		"ち\xE3\x82\x99" => 'ぢ',
		"つ\xE3\x82\x99" => 'づ',
		"て\xE3\x82\x99" => 'で',
		"と\xE3\x82\x99" => 'ど',
		"は\xE3\x82\x99" => 'ば',
		"ひ\xE3\x82\x99" => 'び',
		"ふ\xE3\x82\x99" => 'ぶ',
		"へ\xE3\x82\x99" => 'べ',
		"ほ\xE3\x82\x99" => 'ぼ',
		"は\xE3\x82\x9A" => 'ぱ',
		"ひ\xE3\x82\x9A" => 'ぴ',
		"ふ\xE3\x82\x9A" => 'ぷ',
		"へ\xE3\x82\x9A" => 'ぺ',
		"ほ\xE3\x82\x9A" => 'ぽ',
		"カ\xE3\x82\x99" => 'ガ',
		"キ\xE3\x82\x99" => 'ギ',
		"ク\xE3\x82\x99" => 'グ',
		"ケ\xE3\x82\x99" => 'ゲ',
		"コ\xE3\x82\x99" => 'ゴ',
		"サ\xE3\x82\x99" => 'ザ',
		"シ\xE3\x82\x99" => 'ジ',
		"ス\xE3\x82\x99" => 'ズ',
		"セ\xE3\x82\x99" => 'ゼ',
		"ソ\xE3\x82\x99" => 'ゾ',
		"タ\xE3\x82\x99" => 'ダ',
		"チ\xE3\x82\x99" => 'ヂ',
		"ツ\xE3\x82\x99" => 'ヅ',
		"テ\xE3\x82\x99" => 'デ',
		"ト\xE3\x82\x99" => 'ド',
		"ハ\xE3\x82\x99" => 'バ',
		"ヒ\xE3\x82\x99" => 'ビ',
		"フ\xE3\x82\x99" => 'ブ',
		"ヘ\xE3\x82\x99" => 'ベ',
		"ホ\xE3\x82\x99" => 'ボ',
		"ハ\xE3\x82\x9A" => 'パ',
		"ヒ\xE3\x82\x9A" => 'ピ',
		"フ\xE3\x82\x9A" => 'プ',
		"ヘ\xE3\x82\x9A" => 'ぺ',
		"ホ\xE3\x82\x9A" => 'ポ',
	);

	/**
	 * @var string
	 */
	protected $string;

	/**
	 * @param string $string
	 */
	public function __construct( $string ) {
		$this->string = $string;
	}

	/**
	 * @return string converted string
	 */
	public function convert() {
		$string = preg_replace_callback(
			'/[かきくけこさしすせそたちつてとはひふへほカキクケコサシスセソタチツテトハヒフヘホ]\\x{3099}|[はひふへほハヒフヘホ]\\x{309A}/u',
			function( $matches ) {
				if ( isset( $matches[0] ) ) {
					return $this->targets[$matches[0]];
				}
			},
			$this->string
		);
		return $string;
	}
}
