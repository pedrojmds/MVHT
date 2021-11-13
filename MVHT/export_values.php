<?php
    include("config.php");

    if(isset($_POST["export"])){

        //grabs the variable in the hidden field with the code
        $code = $_POST['dataset_code'];

        // filename to download
        $filename = "manual_values_" . date('Ymd') . ".csv";

        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Type: text/csv");

        $output = fopen("php://output", 'w');
        
        fputcsv($output, array('Term ID 1', 'Entity 1', 'Term ID 2', 'Entity 2', 'Field', 'Value', 'Date' ));
        $query = "SELECT ENT1.TERM_ID AS TERM1, ENT1.NAME AS ENTITY1, ENT2.TERM_ID AS TERM2, ENT2.NAME AS ENTITY2, USER.FIELD, VALUE, DATE FROM SIMILARITY_VALUE JOIN ENTITY ENT1 ON SIMILARITY_VALUE.ID_ENTITY1 = ENT1.ID JOIN ENTITY ENT2 ON SIMILARITY_VALUE.ID_ENTITY2 = ENT2.ID JOIN USER ON SIMILARITY_VALUE.ID_USER = USER.ID WHERE DATASET_CODE = '{$code}'";
        $result = mysqli_query($con, $query);
        while($row = mysqli_fetch_assoc($result)){
            fputcsv($output, $row);
        }
        fclose($output);
    }
?>
