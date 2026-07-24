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
      height: 270px;
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

    @media (max-width: 767.98px) {

      .row-cards > .col:first-child {
        padding-left: 0;
      }
    }
  </style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" 
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous" />
</head>
<body>
  <h3>LOAD MANAGEMENT</h3>
<div class="section-container">
    <div class="row row-cards d-flex flex-wrap">

    <div class="col-auto px-2 first-card">
        <a href="portal.php?page=addnewdevice" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\sim-card.PNG" class="card-icon mb-4 mt-2" alt="Register Device Icon" style="height:100px;"/>
            <div class="card-title">DEVICE MANAGEMENT</div>
            <div class="card-text">Add a new device to your system quickly and securely.</div>
          </div>
        </a>
      </div>

      <!-- Load Checking -->
      <div class="col-auto px-2">
        <a href="portal.php?page=loadchecking" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\smartphone.png" class="card-icon" alt="Load Checking Icon"/>
            <div class="card-title">LOAD CHECKING</div>
            <div class="card-text">Monitor device balances and identify devices that need load.</div>
          </div>
        </a>
      </div>


          <!-- Load Checking -->
      <div class="col-auto px-2">
        <a href="portal.php?page=submittedlist" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\testing.png" class="card-icon mt-2 mb-4" alt="Load Checking Icon"style="height:100px;"/>
            <div class="card-title">CHECKING RESULT</div>
            <div class="card-text">Shows result status of conducted load checking.</div>
          </div>
        </a>
      </div>

            <!-- Load Checking -->
      <div class="col-auto px-2">
        <a href="portal.php?page=unsubmitted" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\no-smartphones.png" class="card-icon mt-2 mb-4" alt="Load Checking Icon"style="height:100px;"/>
            <div class="card-title">DEVICE MONITORING</div>
            <div class="card-text">Shows result if device undergone the device checking.</div>
          </div>
        </a>
      </div>


      <!-- Load Request -->
      <div class="col-auto px-2">
        <a href="portal.php?page=loadrequest" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\sim-req.png" class="card-icon mb-3 mt-3" alt="Load Request Icon" style="height:100px;"/>
            <div class="card-title">LOAD REQUEST</div>
            <div class="card-text">Request load for devices and track pending load requests efficiently.</div>
          </div>
        </a>
      </div>

      <!-- Load Purchase -->
      <div class="col-auto px-2">
        <a href="portal.php?page=loadpurchase" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\phone-sim.png" class="card-icon mb-3 mt-3" alt="Load Purchase Icon" style="height:100px;"/>
            <div class="card-title">LOAD PURCHASE</div>
            <div class="card-text">Purchase and manage SIM load for multiple devices easily.</div>
          </div>
        </a>
      </div>

      <!-- Device Resignation -->
      <div class="col-auto px-2">
        <a href="portal.php?page=resign" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\handphone.png" class="card-icon mb-3 mt-3" alt="Device Resignation Icon" style="height:100px;"/>
            <div class="card-title">DEVICE RESIGNATION</div>
            <div class="card-text">Process device resignations and mark devices as available for reassignment.</div>
          </div>
        </a>
      </div>

      <!-- Device Deployment -->
      <div class="col-auto px-2">
        <a href="portal.php?page=deploy" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\user.png" class="card-icon mb-3 mt-3" alt="Device Deployment Icon" style="height:100px;"/>
            <div class="card-title">DEVICE DEPLOYMENT</div>
            <div class="card-text">Deploy available devices to users with complete assignment details.</div>
          </div>
        </a>
      </div>

       <div class="col-auto px-2">
        <a href="portal.php?page=qrchecking" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\qr.png" class="card-icon mb-3 mt-3" alt="Device Deployment Icon" style="height:100px;"/>
            <div class="card-title">QR CHECKING</div>
            <div class="card-text">Scan and check QR codes for device verification.</div>
          </div>
        </a>
      </div>
       <div class="col-auto px-2">
        <a href="portal.php?page=devicestatus" class="card-link">
          <div class="card-custom">
            <img src="\LM\Home\img\transactions\dmg.png" class="card-icon mb-3 mt-3" alt="Device Deployment Icon" style="height:100px;"/>
            <div class="card-title">DEVICE STATUS</div>
            <div class="card-text">Check the status of devices and their current assignments.</div>
          </div>
        </a>
      </div>

    </div>
</div>


  
  </div>
</body>
</html>