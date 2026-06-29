function fitFileManager() {
    const block = document.getElementById('fm-main-block');

    if (!block) {
        return;
    }

    const content = block.closest('.admin-content');
    const paddingBottom = content
        ? parseFloat(getComputedStyle(content).paddingBottom) || 0
        : 0;
    const top = block.getBoundingClientRect().top;

    block.style.height = `${Math.max(200, window.innerHeight - top - paddingBottom)}px`;
}

function loadFileManager() {
    fitFileManager();
    window.addEventListener('resize', fitFileManager);

    const mount = document.getElementById('fm-main-block');

    if (!mount || document.getElementById('file-manager-script')) {
        return;
    }

    const script = document.createElement('script');
    script.id = 'file-manager-script';
    script.src = mount.dataset.fileManagerSrc;
    script.addEventListener('load', () => {
        fitFileManager();
        requestAnimationFrame(fitFileManager);
    });
    document.body.appendChild(script);
}

document.addEventListener('DOMContentLoaded', loadFileManager);
