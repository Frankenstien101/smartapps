<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Transactions</title>
  <style>
    h3 {
      margin-top: 50px;
      margin-bottom: 20px;
      padding-left: 15px;
      font-weight: bold;
    }
    .section-container {
      padding-left: 15px;
      padding-right: 15px;
    }
  
    .card-custom {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 200px;
      height: 210px;
      margin: 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 15px;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .card-custom:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 20px rgba(0,0,0,0.2);
    }
    .card-icon {
      width: 180px;
      height: 130px;
      object-fit: contain;
    }
    .card-title {
      margin-top: 10px;
      font-weight: bold;
      font-size: 1rem;
      text-align: center;
      color:chocolate;
    }
    .card-text {
      margin-top: 6px;
      font-size: 0.75rem;
      color: #555;
      text-align: center;
    }

    /* Adjust margins for mobile to align the first card */
    @media (max-width: 767.98px) {
      /* Remove side margins for first child to align precisely */
      .row-cards > .col:first-child {
        padding-left: 0;
      }
    }
  </style>
  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" 
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous" />
</head>
<body>
  <h3>REPORTS</h3>
<div class="section-container">
    <div class="row row-cards d-flex flex-wrap">
      <!-- Register New Device -->
      <div class="col-auto px-2 first-card">
        <a href="portal.php?page=devicelist" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\report\phone.PNG" class="card-icon mb-4 mt-2" alt="Register Device Icon" style="height:100px;"/>
            <div class="card-title">DEVICE LIST</div>
          </div>
        </a>
      </div>

      <!-- Load Checking -->
      <div class="col-auto px-2">
        <a href="portal.php?page=loadcheckingreport" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\report\devicecheck.png" class="card-icon  mt-2 mb-3"  alt="Load Checking Icon" style="height:100px;"/>
            <div class="card-title">LOAD CHECKING RESULT</div>
          </div>
        </a>
      </div>


          <!-- Load Checking -->
      <div class="col-auto px-2">
        <a href="portal.php?page=loadrequest" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\report\forload.png" class="card-icon mt-2 mb-4" alt="Load Checking Icon"style="height:100px;"/>
            <div class="card-title">FOR LOAD LIST</div>
          </div>
        </a>
      </div>

      <!-- Load Request -->
      <div class="col-auto px-2">
        <a href="portal.php?page=purchasereport" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\report\loadpurchase.png" class="card-icon mb-3 mt-3" alt="Load Request Icon" style="height:100px;"/>
            <div class="card-title">LOAD PURCHASE</div>
          </div>
        </a>
      </div>

      <!-- Resigned Devices Report -->
      <div class="col-auto px-2">
        <a href="portal.php?page=report_resigned" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\report\blocked.png" class="card-icon mb-3 mt-3" alt="Resigned Report Icon" style="height:100px;"/>
            <div class="card-title">RESIGNED REPORT</div>
          </div>
        </a>
      </div>


      <div class="col-auto px-2">
        <a href="portal.php?page=QR-generator" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\report\QRGEN.png" class="card-icon mb-3 mt-3" alt="Resigned Report Icon" style="height:100px;"/>
            <div class="card-title">QR CODE GENERATOR</div>
          </div>
        </a>
      </div>


       <div class="col-auto px-2">
        <a href="portal.php?page=damagereport" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\dmg.png" class="card-icon mb-3 mt-3" alt="Resigned Report Icon" style="height:100px;"/>
            <div class="card-title">DEVICE I.R COMPLIANCE</div>
          </div>
        </a>
      </div>

    </div>
</div>

</div>
</body>
</html>