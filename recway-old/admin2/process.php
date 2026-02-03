<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h1>Table Data</h1>';

    // Loop through all table data in POST
    foreach ($_POST as $key => $value) {
        if (strpos($key, '_col1') !== false) {
            $tableId = str_replace('_col1', '', $key);
            $tableCaption = $_POST['table_caption'];
            $rows = count($value);

            echo "<h2>$tableCaption</h2>";
            echo "<table>";
            echo '<thead><tr><th>Column 1</th><th>Column 2</th><th>Column 3</th></tr></thead>';
            echo '<tbody>';

            for ($i = 0; $i < $rows; $i++) {
                $col1 = $value[$i];
                $col2 = $_POST[$tableId.'_col2'][$i];
                $col3 = $_POST[$tableId.'_col3'][$i];
                echo "<tr><td>$col1</td><td>$col2</td><td>$col3</td></tr>";
            }

            echo '</tbody></table>';
        }
    }
}
?>