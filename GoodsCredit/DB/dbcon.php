<?php
try {
    $conn = new PDO(
      //  "sqlsrv:Server=localhost;Database=SIDJAN",
        "sqlsrv:Server=172.40.0.81;Database=SIDJAN",
        "sa",
        'bspi.@dm1n'
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
} catch (PDOException $e) {
    
    echo "❌ Error connecting to Server: " . $e->getMessage();
}
?>
