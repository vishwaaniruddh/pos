<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video List</title>
    <style>
        :root {
            --primary-color: #4285f4;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --border-color: #e0e0e0;
            --hover-color: #f1f1f1;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', Arial, sans-serif;
        }
        
        body {
            background-color: white;
            color: var(--text-color);
            line-height: 1.5;
        }
        
        .top-bar {
            background-color: white;
            padding: 12px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.3rem;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .video-list {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .video-item {
            display: flex;
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 4px;
            transition: background-color 0.2s;
            cursor: pointer;
            border: 1px solid var(--border-color);
        }
        
        .video-item:hover {
            background-color: var(--hover-color);
        }
        
        .thumbnail-container {
            width: 240px;
            min-width: 240px;
            height: 135px;
            position: relative;
            margin-right: 15px;
            border-radius: 4px;
            overflow: hidden;
            background-color: #f0f0f0;
        }
        
        .thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .play-icon svg {
            width: 20px;
            height: 20px;
            fill: white;
            margin-left: 3px;
        }
        
        .video-item:hover .play-icon {
            opacity: 1;
        }
        
        .video-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .video-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-color);
        }
        
        .video-description {
            font-size: 0.9rem;
            color: #606060;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Video Player Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .video-modal {
            width: 90%;
            max-width: 1000px;
            position: relative;
        }
        
        .close-btn {
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .video-player {
            width: 100%;
            aspect-ratio: 16/9;
            background-color: #000;
        }
        
        .video-player iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        @media (max-width: 768px) {
            .video-item {
                flex-direction: column;
            }
            
            .thumbnail-container {
                width: 100%;
                height: auto;
                aspect-ratio: 16/9;
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="logo">Admin Video Tutorials</div>
    </div>
    
    <div class="video-list" id="videoListContainer">
        <!-- Videos will be loaded here dynamically -->
    </div>
    
    <!-- Video Player Modal -->
    <div class="modal-overlay">
        <div class="video-modal">
            <button class="close-btn">&times;</button>
            <div class="video-player">
                <iframe src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoListContainer = document.getElementById('videoListContainer');
            const modalOverlay = document.querySelector('.modal-overlay');
            const closeBtn = document.querySelector('.close-btn');
            const videoPlayer = document.querySelector('.video-player iframe');
            
            // Video data - IDs and titles
            const videos = [
                { id: 'WUV8K7itFaw', title: 'Login System Tutorial' },
                { id: 'RKpHPUt9ANI', title: 'Edit Website Content' },
                { id: '2n8mKRp4_Y4', title: 'Add Product Tutorial' },
                { id: 'hxLsDnoZIqA', title: 'Edit Product Tutorial' },
                { id: 'DWGhhMl1Pzk',  title: 'Add Coupon'},
                { id: 'IANLwwLDkB0',  title: 'Coupon Usage Restriction'},
                { id: 'yXzQct-nMio',  title: 'Coupon Usage Limits Section'},
                
                
                
            ];
            
            // Generate video items
            videos.forEach(video => {
                const videoItem = document.createElement('div');
                videoItem.className = 'video-item';
                videoItem.setAttribute('data-video-id', video.id);
                videoItem.setAttribute('data-title', video.title);
                
                // YouTube thumbnail URLs (maxresdefault or hqdefault as fallback)
                const thumbnailUrl = `https://img.youtube.com/vi/${video.id}/maxresdefault.jpg`;
                const fallbackThumbnailUrl = `https://img.youtube.com/vi/${video.id}/hqdefault.jpg`;
                
                videoItem.innerHTML = `
                    <div class="thumbnail-container">
                        <img src="${thumbnailUrl}" alt="${video.title}" class="thumbnail" onerror="this.src='${fallbackThumbnailUrl}'">
                        <div class="play-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title">${video.title}</h3>
                        <p class="video-description">Click to watch the tutorial video about ${video.title.toLowerCase()}.</p>
                    </div>
                `;
                
                videoListContainer.appendChild(videoItem);
                
                // Add click event to the newly created video item
                videoItem.addEventListener('click', function() {
                    const videoId = this.getAttribute('data-video-id');
                    const title = this.getAttribute('data-title');
                    
                    // Set video source
                    videoPlayer.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
                    
                    // Update document title
                    document.title = `${title} - Admin Video Tutorials`;
                    
                    // Show modal
                    modalOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });
            
            // Close modal
            closeBtn.addEventListener('click', closeModal);
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === modalOverlay) {
                    closeModal();
                }
            });
            
            function closeModal() {
                modalOverlay.classList.remove('active');
                videoPlayer.src = '';
                document.body.style.overflow = 'auto';
                document.title = 'Admin Video Tutorials';
            }
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>