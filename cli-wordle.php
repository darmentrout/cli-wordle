<?php
include __DIR__ . '/wordlist.php';

$today = date_create( date( 'Y-m-d H:i:s', time() ) );
$today_int = $today->format('ymd');
$tomorrow = date_create( date( 'Y-m-d', strtotime( 'tomorrow midnight' ) ) );
$interval = date_diff( $today, $tomorrow );
$next_word_interval = $interval->format('Next word in %Hh %Im %Ss');

$ciphering = 'AES-128-CTR';
$iv_length = openssl_cipher_iv_length( $ciphering );
$options = 0;
$encryption_iv = '1549825741653284';
$encryption_key = 'WordleIsFun'; 

$storage_file_name = 'storage.json';
if( file_exists( $storage_file_name ) ){
    $storage_json = file_get_contents( $storage_file_name );    
    $storage_array = json_decode( $storage_json, true );
    $time = $storage_array['timestamp'];
    $daily_word = openssl_decrypt( $storage_array['dailyword'], $ciphering, $encryption_key, $options, $encryption_iv );
}
else{
    $time = $today_int;
}

if( $today_int > $time || !file_exists( $storage_file_name ) ){
    $daily_word = $wordlist[ rand( 0, count( $wordlist ) -1 ) ];
    $storage_array = [
        "timestamp" => $today_int,
        "dailyword" => $daily_word,
        "potential" => 'abcdefghijklmnopqrstuvwxyz',
        "attempts"  => []
    ];
}
else{
    if( count( $storage_array['attempts'] ) >= 6 ){
        die( "You have already guessed six times! \r\nToday's word is $daily_word. \r\n$next_word_interval." . PHP_EOL );
    }
}

$potential_letters = $storage_array['potential'];

$guess = strtolower( htmlspecialchars( trim( $argv[1] ) ) );


function list_attempts(){
    global $storage_array;
    global $hit_miss_str;
    echo "Guesses:" . PHP_EOL;
    if( count( $storage_array['attempts'] ) !== 0 ){
        foreach( $storage_array['attempts'] as $index => $attempt ){
            $index++;
            echo "  \033[0m$index " . $attempt . PHP_EOL;
        }    
    }
}

$guess_length = strlen( $guess );
if( $guess_length !== 5 ){
    die( "Your guess must be five characters long. Yours was $guess_length characters." . PHP_EOL );
}

if( !in_array($guess, $wordlist) ){
    die( 'That word is not in the list' . PHP_EOL );
}

if( $guess === $daily_word ){
    $final_count = count( $storage_array['attempts'] ) + 1;
    echo list_attempts() . "  \033[0m$final_count \033[92m$guess" . PHP_EOL;
    die( "\033[91mSplendid! \033[0mYou guessed the daily word: $daily_word. \r\n$next_word_interval." . PHP_EOL );
}

$daily_word_array = str_split( $daily_word );
$guess_array = str_split( $guess );
$hit_miss_array = [];
$display_letters = $potential_letters;
foreach( $guess_array as $index => $letter ){
    if( $letter === $daily_word_array[$index] ){
        $hit_miss_array[$index] = "\033[92m$letter";
        $display_letters = substr_replace( $display_letters, "\033[92m$letter\033[0m", strpos($display_letters, $letter), 1);
    }
    elseif( in_array( $letter, $daily_word_array ) && $letter !== $daily_word_array[$index] ){
        $hit_miss_array[$index] = "\033[93m$letter";
        $display_letters = substr_replace( $display_letters, "\033[33m$letter\033[0m", strpos($display_letters, $letter), 1);
    }
    else{
        $hit_miss_array[$index] = "\033[90m$letter";
        if( strpos($potential_letters, $letter) !== FALSE ){
            $display_letters = substr_replace( $display_letters, '', strpos($display_letters, $letter), 1);
            $potential_letters = substr_replace( $potential_letters, '', strpos($potential_letters, $letter), 1);
        }
    }
}
list_attempts();
$hit_miss_str = implode( $hit_miss_array );
$current_attempt = count( $storage_array['attempts'] ) + 1;
echo "  \033[0m$current_attempt $hit_miss_str" . PHP_EOL;
echo "\033[0mLetters: $display_letters" . PHP_EOL;

if( count( $storage_array['attempts'] ) === 5 ){
    die( "\033[91mYou ran out of guesses!\r\n\033[0mToday's word is $daily_word. \r\n$next_word_interval." . PHP_EOL );
}

$encrypted_word = openssl_encrypt( $daily_word, $ciphering, $encryption_key, $options, $encryption_iv );   

if( isset($storage_array) ){
    $output_array = [
        "timestamp" => $today_int,
        "dailyword" => $encrypted_word,
        "potential" => $potential_letters,
        "attempts"  => $storage_array['attempts']
    ];
}
else{
    $output_array = [
        "timestamp" => $today_int,
        "dailyword" => $encrypted_word,
        "potential" => $potential_letters,
        "attempts"  => []
    ];
}
$output_array['attempts'][] = $hit_miss_str;
file_put_contents( $storage_file_name, json_encode($output_array), 0 );