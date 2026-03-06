<?php
session_start();

function loqui_cum_daemonio($mandatum) {
    $fp = fsockopen("127.0.0.1", 8080, $errno, $errstr, 10);
    if (!$fp) {
        return "500|Error|Daemonium non respondet";
    }
    fwrite($fp, $mandatum . "\n");
    $responsum = fgets($fp, 4096);
    fclose($fp);
    return trim($responsum);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nomen = trim($_POST["nomen"]);
    $actio = $_POST["actio"];

    if (!empty($nomen)) {
        if ($actio == "intrare") {
            $resp = loqui_cum_daemonio("INTRARE|" . $nomen);
            $partes = explode("|", $resp);
            if ($partes[0] == "200") {
                $_SESSION["usor"] = $nomen;
                header("Location: fabulatio.php");
                exit();
            } else {
                $error = "Nomen non inventum. Creare novum?";
            }
        } elseif ($actio == "creare") {
            $resp = loqui_cum_daemonio("CREARE_USOREM|" . $nomen);
            $partes = explode("|", $resp);
            if ($partes[0] == "200") {
                $_SESSION["usor"] = $nomen;
                header("Location: fabulatio.php");
                exit();
            } else {
                $error = "Nomen iam exstat.";
            }
        }
    } else {
        $error = "Nomen vacuum est.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Porta Introitus</title>
    <style>
        body { background-color: black; color: #00FF00; font-family: "Courier New", Courier, monospace; }
        input[type="text"], input[type="submit"] { background-color: black; color: #00FF00; border: 1px solid #00FF00; }
        a { color: #00FF00; }
    </style>
</head>
<body>
    <h1>Salve Viator!</h1>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST" action="index.php">
        <label>Nomen tuum:</label><br>
        <input type="text" name="nomen" autofocus><br><br>
        <input type="submit" name="actio" value="intrare"> (Intrare)<br><br>
        <input type="submit" name="actio" value="creare"> (Creare)
    </form>
</body>
</html>
