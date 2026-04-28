<!DOCTYPE html>
<html>
<head>
    <title>Agent Timestamp API Test</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        button { padding: 10px 20px; font-size: 16px; margin: 5px; cursor: pointer; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Agent Timestamp API Test</h1>
    <p>API URL: <code>https://vagabondish-ahmad-entertainingly.ngrok-free.dev/API/Logs/AgentTimeStamp/save_agent_timestamp.php</code></p>
    
    <button onclick="testAPI()">Test API</button>
    <button onclick="testUpdate()">Test Update (Same Sync ID)</button>
    <button onclick="testMultiple()">Test Multiple Records</button>
    
    <h3>Response:</h3>
    <pre id="response">Click a button to test...</pre>

    <script>
        const API_URL = "https://vagabondish-ahmad-entertainingly.ngrok-free.dev/API/Logs/AgentTimeStamp/save_agent_timestamp.php";
        
        async function testAPI() {
            const data = {
                sync_id: "test_" + new Date().toISOString().slice(0,19).replace(/:/g, '-'),
                records: [{
                    LINE_ID: 1001,
                    COMPANY_ID: "COMP001",
                    SITE_ID: "SITE_A",
                    AGENT_ID: "AGENT_JOHN_001",
                    VEHICLE_ID: "VEH_1234",
                    DELIVERY_DATE: new Date().toISOString().split('T')[0],
                    LAT_CAPTURED: 14.5995,
                    LONG_CAPTURED: 120.9842,
                    TIME_STAMP: new Date().toISOString().slice(0,19).replace('T', ' '),
                    BATTERY_PERCENTAGE: 85.5,
                    GPS_ACCURACY: 3.2,
                    TIME_MINUTES: 45.5
                }]
            };
            
            await sendRequest(data);
        }
        
        async function testUpdate() {
            const data = {
                sync_id: "test_update_sync_id",
                records: [{
                    LINE_ID: 1001,
                    COMPANY_ID: "COMP001",
                    SITE_ID: "SITE_A",
                    AGENT_ID: "AGENT_JOHN_001",
                    VEHICLE_ID: "VEH_1234",
                    DELIVERY_DATE: new Date().toISOString().split('T')[0],
                    LAT_CAPTURED: 14.5995,
                    LONG_CAPTURED: 120.9842,
                    TIME_STAMP: new Date().toISOString().slice(0,19).replace('T', ' '),
                    BATTERY_PERCENTAGE: 95.0,
                    GPS_ACCURACY: 2.5,
                    TIME_MINUTES: 50.0
                }]
            };
            
            await sendRequest(data);
        }
        
        async function testMultiple() {
            const data = {
                sync_id: "test_multiple_" + Date.now(),
                records: [
                    {
                        LINE_ID: 2001,
                        COMPANY_ID: "COMP002",
                        SITE_ID: "SITE_B",
                        AGENT_ID: "AGENT_PETER_001",
                        VEHICLE_ID: "VEH_9999",
                        DELIVERY_DATE: new Date().toISOString().split('T')[0],
                        LAT_CAPTURED: 14.6234,
                        LONG_CAPTURED: 120.9987,
                        TIME_STAMP: new Date().toISOString().slice(0,19).replace('T', ' '),
                        BATTERY_PERCENTAGE: 92.0,
                        GPS_ACCURACY: 3.0,
                        TIME_MINUTES: 60.0
                    },
                    {
                        LINE_ID: 2002,
                        COMPANY_ID: "COMP002",
                        SITE_ID: "SITE_B",
                        AGENT_ID: "AGENT_PETER_001",
                        VEHICLE_ID: "VEH_9999",
                        DELIVERY_DATE: new Date().toISOString().split('T')[0],
                        LAT_CAPTURED: 14.6278,
                        LONG_CAPTURED: 121.0012,
                        TIME_STAMP: new Date().toISOString().slice(0,19).replace('T', ' '),
                        BATTERY_PERCENTAGE: 89.5,
                        GPS_ACCURACY: 2.8,
                        TIME_MINUTES: 65.0
                    }
                ]
            };
            
            await sendRequest(data);
        }
        
        async function sendRequest(data) {
            const responseDiv = document.getElementById('response');
            responseDiv.innerHTML = "⏳ Sending request...\n\n" + JSON.stringify(data, null, 2);
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                responseDiv.innerHTML = "✅ HTTP " + response.status + "\n\n";
                responseDiv.innerHTML += JSON.stringify(result, null, 2);
                
                if (result.action === 'created') {
                    responseDiv.innerHTML = "✨ NEW FILE CREATED!\n\n" + responseDiv.innerHTML;
                } else if (result.action === 'updated') {
                    responseDiv.innerHTML = "🔄 EXISTING FILE UPDATED!\n\n" + responseDiv.innerHTML;
                }
            } catch (error) {
                responseDiv.innerHTML = "❌ ERROR: " + error.message;
            }
        }
    </script>
</body>
</html>