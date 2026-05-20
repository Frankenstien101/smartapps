<?php
$site_name = "Kayatay";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $site_name ?> - Free Porn Videos</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #0f0f0f; 
            color: #fff; 
            margin:0; padding:0; 
        }
        header { 
            background: linear-gradient(#ff0000, #8B0000); 
            padding: 30px; 
            text-align: center; 
        }
        .container { 
            max-width: 1400px; 
            margin: 30px auto; 
            padding: 0 15px; 
        }
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        .video-card {
            background: #1a1a1a;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .video-card:hover { 
            background: #222;
            transform: scale(1.05); 
        }
        .play-icon {
            font-size: 80px;
            margin: 30px 0;
            color: #ff0000;
        }
        .video-info h3 {
            margin: 10px 0;
            font-size: 16px;
            line-height: 1.4;
        }
        .source {
            color: #ffcc00;
            font-size: 14px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.98);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            width: 90%;
            max-width: 1000px;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }
        .modal-header {
            background: #1a1a1a;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .close {
            font-size: 35px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<header>
    <h1><?= $site_name ?></h1>
    <p>Click on any video to play directly</p>
</header>

<div class="container">
    <h2>🔥 Trending Videos</h2>
    <div class="video-grid" id="videoGrid"></div>
</div>

<!-- Video Player Modal -->
<div class="modal" id="videoModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Now Playing</h3>
            <span class="close" onclick="closeModal()">×</span>
        </div>
        <div id="modalPlayer" style="position:relative; padding-top:56.25%; background:#000;"></div>
    </div>
</div>

<script>
// Video List (Easy to add more)
const videos = [
    {
        title: "Big Ass Latina Hard Fuck",
        source: "XVideos",
        embed: "https://www.xvideos.com/embedframe/kpplhuocd04"
    },
    {
        title: "Busty MILF Creampie",
        source: "XVideos",
        embed: "https://www.xvideos.com/embedframe/oufkdvpd73f"
    },
    {
        title: "Japanese Office Lady",
        source: "XVideos",
        embed: "https://www.xvideos.com/embedframe/oohdvlee3fe"
    },
    {
        title: "Stepmom Secret Sex",
        source: "Pornhub",
        embed: "https://www.pornhub.com/embed/ph5f8a2b3c4d5e"
    },
    {
        title: "POV Deepthroat",
        source: "SpankBang",
        embed: "https://spankbang.com/embed/xxxx"   // Change with real one later
    },
    {
        title: "Amateur Couple Real Fuck",
        source: "xHamster",
        embed: "https://xhamster.com/embed/xxxx"
    }
];

function renderVideos() {
    const grid = document.getElementById('videoGrid');
    grid.innerHTML = '';

    videos.forEach(video => {
        const card = document.createElement('div');
        card.className = 'video-card';
        card.innerHTML = `
            <div class="play-icon">▶</div>
            <div class="video-info">
                <h3>${video.title}</h3>
                <span class="source">${video.source}</span>
            </div>
        `;
        card.onclick = () => playVideo(video);
        grid.appendChild(card);
    });
}

function playVideo(video) {
    document.getElementById('modalTitle').textContent = video.title;
    document.getElementById('modalPlayer').innerHTML = `
        <iframe src="${video.embed}" 
                style="position:absolute; top:0; left:0; width:100%; height:100%; border:none;" 
                allowfullscreen allow="autoplay" frameborder="0"></iframe>
    `;
    document.getElementById('videoModal').style.display = "flex";
}

function closeModal() {
    document.getElementById('videoModal').style.display = "none";
    document.getElementById('modalPlayer').innerHTML = "";
}

renderVideos();

</script>

</body>
</html>