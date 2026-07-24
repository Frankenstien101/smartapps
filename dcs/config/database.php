<?php
try {
    $conn = new PDO(
        "sqlsrv:server=172.40.0.81,1433;Database=BSPIDBNEW",
        "sa",
        'bspi.@dm1n'
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
} catch (PDOException $e) {
    
    echo "❌ Error connecting to Server: " . $e->getMessage();
    header('Location: /db_error.php');
}
?>