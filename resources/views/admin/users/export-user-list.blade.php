<?php
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment;Filename=CCFC-User-List-".date('Y-m-d').".xls");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CCFC-User-List-<?=date('Y-m-d')?></title>
</head>
<body>
    <table border="1" cellpadding="5" cellspacing="3" style="border-collapse: collapse;" width="800" align="center">
        <thead>
            <tr>
                <th colspan="2">
                    <h1 style="text-align:center;">CCFC User List</h1>
                    <h5 style="text-align:center;">Generate Date : <u><?=date('M d, Y')?></u></h5>
                </th>
            </tr>
            <tr>
                <th>Sl No.</th>
                <th>Member Code</th>
            </tr>
        </thead>
        <tbody>
            <?php if($response) { $sl=1; foreach($response as $row){?>
                <tr style="text-align: center;">
                    <td><?=$sl++?></td>
                    <td><?=$row['user_code']?></td>
                </tr>
            <?php } }?>
        </tbody>
    </table>
</body>
</html>