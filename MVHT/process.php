<?php
    session_start();
    
    include("config.php");

    $dscode = $_SESSION['code'];

    if (isset($_POST['formSubmit'])) {
        $varSimilarity = $_POST['rangeSlider'];

        if (isset($varSimilarity)){
            $date = date("Y-m-d");
            if (isset($_SESSION['ent1']) && isset($_SESSION['ent2'])) {
                $sql = "INSERT INTO SIMILARITY_VALUE (DATASET_CODE, ID_ENTITY1, ID_ENTITY2, ID_USER, VALUE, DATE) VALUES ('{$dscode}', {$_SESSION['ent1']},{$_SESSION['ent2']}, {$_SESSION['userid']}, {$varSimilarity}, '{$date}')";

                if (mysqli_query($con, $sql)) {
                    echo "New record created successfully";

                } else {
                    echo "Error: " . $sql . "<br>" . mysqli_error($con);
                }
            }
        }
    }

    
    mysqli_close($con);

    // header("Location:webapp.php");
    header("Location:retrieve_data.php?code={$_SESSION['code']}");
?>
