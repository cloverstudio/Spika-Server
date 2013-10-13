<?php
$emojiAry = array(
    "😉",
    "😍",
    "😘",
    "😚",
    "😗",
    "😙",
    "😜",
    "😝",
    "😛",
    "😳",
    "😁",
    "😔",
    "😌",
    "😒",
    "😞",
    "😣",
    "😢",
    "😂",
    "😭",
    "😪",
    "😥",
    "😰",
    "😅",
    "😓",
    "😩",
    "😫",
    "😨",
    "😱",
    "😠",
    "😡",
    "😤",
    "😖",
    "😆",
    "😋",
    "😷",
    "😲",
    "😵",
    "😴",
    "😎",
    "😟",
    "😦",
    "😧",
    "😈",
    "😐",
    "😬",
    "😮",
    "👿",
    "😕",
    "😯",
    "😶",
    "😇",
    "👳",
    "👲",
    "😑",
    "😏",
    "🐶",
    "🐺",
    "🐱",
    "🐭",
    "🐹",
    "🐰",
    "🐸",
    "🐯",
    "🐽",
    "🐷",
    "🐻",
    "🐨",
    "🐮",
    "🐗",
    "🐵",
    "🐒",
    "🐼",
    "🐘",
    "🐑",
    "🐴",
    "🐧",
    "🐦",
    "🐤",
    "🐥",
    "🐢",
    "🐍",
    "🐔",
    "🐣",
    "🐛",
    "🐝",
    "🐞",
    "🐞",
    "🐠",
    "🐚",
    "🐙",
    "🐙",
    "🐌",
    "🐟",
    "🐬",
    "🐳",
    "🐋",
    "🐃",
    "🐀",
    "🐏",
    "🐄",
    "🐅",
    "🐇",
    "🐇",
    "🐉"
);
$colNumPerPage = 10;
$rowNumPerPage = 6;

$viewPort = 320;
$height = 200;

$cellWidth = $viewPort / $colNumPerPage;
$cellHeight = $height / $rowNumPerPage;
?>
<!DOCTYPE html>
<html class="no-js">
<head>

    <style>
        .emojiBtn {
            display: block;
            position: absolute;
            padding: 3px;
            margin: 2px;
            background: #f1f1f1;
            text-decoration: none;
        }
    </style>

    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title></title>
    <meta name="description" content="">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="<?php echo $viewPort ?>">
    <meta name="viewport" content="width=device-width"
    ," initial-scale=1">
    <meta http-equiv="cleartype" content="on">

</head>
<body>
<?php for ($i = 0; $i < count($emojiAry); $i++) { ?>

    <a href="<?php echo $emojiAry[$i] ?>" class="emojiBtn"
       style="left:<?php echo ((int)((int)$i / (int)$rowNumPerPage)) * $cellWidth ?>px;top:<?php echo $i % $rowNumPerPage * $cellHeight ?>px">
        <?php echo $emojiAry[$i] ?>
    </a>

<?php } ?>

</body>
</html>