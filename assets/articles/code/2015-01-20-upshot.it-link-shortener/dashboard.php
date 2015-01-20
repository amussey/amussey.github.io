<?php
define("ANALYTICS_JSON", "./analytics.json");

$analytics = json_decode(file_get_contents(ANALYTICS_JSON), true);

?><!DOCTYPE html>
<html lang="en">
<head>
    <title>Link Shortener Analytics</title>

    <style>
    html, body {
        background: rgb(224, 224, 224);
        font-family: "Helvetica Neue", Arial, Helvetica, sans-serif;
        font-size: 15px;
    }
    table {
        width: 100%;
    }
    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
    }
    thead {
        color: #fff;
        background-color: #3F3F3F;
    }
    thead a {
        color :#aaa;
    }
    .inline-image  {
        max-width: 600px;
        max-height: 600px;
        margin: 10px;
    }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>
                    Image <?php
                    if (isset($_GET["inline"])) {
                        ?><a href="?">(Hide Inline Images)</a><?php
                    } else {
                        ?><a href="?inline">(Show Images Inline)</a><?php
                    }
                    ?>
                </th>
                <th>Hits</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($analytics as $key => $value) { ?>
            <tr>
                <td style="text-align:center;">
                    <?php
                    if (isset($_GET["inline"])) {
                        ?>
                        <a href="../<?=$key ?>" target="_blank">
                            <img src="../<?=$key ?>" class="inline-image">
                        </a>
                        <?php
                    } else {
                        echo $key;
                    }
                    ?>
                </td>
                <td style="text-align:center;"><?=$value ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <script>
    </script>
</body>
</html>
