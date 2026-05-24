<?php
if (defined('SPA_LAYOUT')) {
    return;
}
?>
<!-- Compatibility wrapper for legacy fade-out triggers -->
<div class="page-loader-wrapper" style="display: none;"></div>

<script>
    (function() {
        // Ensure global top loading bar exists
        let bar = document.getElementById('top-loading-bar');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'top-loading-bar';
            bar.style.cssText = 'position: fixed; top: 0; left: 0; height: 3px; width: 0%; z-index: 999999; transition: width 0.4s ease, opacity 0.3s ease;';
            
            const style = document.createElement('style');
            style.id = 'top-loading-bar-styles';
            style.innerHTML = `
                #top-loading-bar {
                    background-color: #000000;
                    box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
                }
                .dark #top-loading-bar {
                    background-color: #ffffff;
                    box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
                }
            `;
            document.head.appendChild(style);
            document.body.insertBefore(bar, document.body.firstChild);
        }
        
        // Initial page load simulation
        if (bar.style.width === '0%' || !bar.style.width) {
            bar.style.opacity = '1';
            bar.style.width = '30%';
            
            const completeLoading = () => {
                bar.style.width = '100%';
                setTimeout(() => {
                    bar.style.opacity = '0';
                    setTimeout(() => { bar.style.width = '0%'; }, 300);
                }, 200);
            };
            
            if (document.readyState === 'complete') {
                completeLoading();
            } else {
                window.addEventListener('load', completeLoading);
            }
        }
    })();
</script>