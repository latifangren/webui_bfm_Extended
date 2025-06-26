<?php
// redirect_alt.php
// Alternatif redirect menggunakan HTML meta refresh

$target_url = "https://adblock.turtlecute.org/";
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0;url=<?php echo $target_url; ?>">
    <title>Redirecting...</title>
    <style>
        body {
            background-color: transparent;
        }
    </style>
</head>
<body>
    <p>Loading.... please wait, <a href="<?php echo $target_url; ?>">Click here if not redirect</a>.</p>

</body>
</html>