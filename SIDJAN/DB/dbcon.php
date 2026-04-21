<?php
try {
    $conn = new PDO(
        "sqlsrv:server=tcp:bspidbservernew.database.windows.net,1433;Database=BSPIDBNEW",
        "sqladmin",
        'b$p1.@dm1n'
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
} catch (PDOException $e) {
    
    echo "❌ Error connecting to Server: " . $e->getMessage();
}
?>
