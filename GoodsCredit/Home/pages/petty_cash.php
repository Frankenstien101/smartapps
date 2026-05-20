
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Petty Cash Management</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"/>
</head>

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
    /* Card styles */
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

<div class="d-flex align-items-center mb-3">
  <button type="button" class="btn btn-secondary" style="margin-right: 15px;" onclick="history.back();">
    ← Back
  </button>
  <h4 class="mb-0 fw-bold text-uppercase">Petty Cash Management</h4>
</div>
<hr>

<div class="section-container">
  <div class="row row-cards d-flex flex-wrap">
    <!-- Request -->
    <div class="col-auto px-2 first-card">
      <a href="#" class="card-link">
        <div class="card-custom">
          <img src="\BlueBook\Home\img\pettycash\request.jpg" class="card-icon" alt="Upload Orders Icon"/>
          <div class="card-title">🧾 Request</div>
          <div class="card-text">Submit a new petty cash request for approval.</div>
        </div>
      </a>
    </div>

    <!-- Approval -->
    <div class="col-auto px-2">
      <a href="#" class="card-link">
        <div class="card-custom">
          <img src="\BlueBook\Home\img\transactions\payments.jpg" class="card-icon" alt="Order View Icon"/>
          <div class="card-title">👍 Approval</div>
          <div class="card-text">Review and approve submitted cash requests.</div>
        </div>
      </a>
    </div>

    <!-- Release -->
    <div class="col-auto px-2">
      <a href="#" class="card-link">
        <div class="card-custom">
          <img src="\BlueBook\Home\img\transactions\debit.png" class="card-icon" alt="Order Preparation Icon"/>
          <div class="card-title">💰 Release</div>
          <div class="card-text">Disburse approved funds and record transactions.</div>
        </div>
      </a>
    </div>

    <!-- Liquidation -->
    <div class="col-auto px-2">
      <a href="#" class="card-link">
        <div class="card-custom">
          <img src="\BlueBook\Home\img\transactions\deposit.jpg" class="card-icon" alt="Delivery Retry Icon"/>
          <div class="card-title">🏦 Liquidation</div>
          <div class="card-text">Submit liquidation reports with receipts and proofs.</div>
        </div>
      </a>
    </div>
  </div>
</div>



  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>


