<?php
    include("config.php");

    $id = $_POST['id'];

    if($id > 0) {
            // Check if record exists
        $checkRecord = mysqli_query($con, "SELECT * FROM DATASET WHERE ID= {$id}");
        $totalRows = mysqli_num_rows($checkRecord);

        if($totalRows > 0) {
                // Delete record
            $query = "DELETE FROM DATASET WHERE ID = {$id}";
            mysqli_query($con, $query);
            echo 1;
            exit;
        }
    }
    echo 0;
    exit;
?>