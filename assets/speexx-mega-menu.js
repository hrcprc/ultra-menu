(function () {
  function setDescription(link) {
    const panel = link.closest('.speexx-mega-sub-menu');
    if (!panel) return;

    const descriptionBox = panel.querySelector('.speexx-mega-description');
    if (!descriptionBox) return;

    panel.querySelectorAll('.speexx-mega-products > li > .menu-link').forEach((itemLink) => {
      itemLink.classList.remove('is-active');
    });

    link.classList.add('is-active');

    const title = link.textContent.trim();
    const descriptionHtml = link.getAttribute('data-mega-description-html') || '';
    const imageUrl = link.getAttribute('data-mega-image') || '';

    descriptionBox.textContent = '';

    if (imageUrl) {
      const image = document.createElement('img');
      image.src = imageUrl;
      image.alt = title;
      image.className = 'speexx-mega-description__image';
      descriptionBox.appendChild(image);
    }

    const heading = document.createElement('h3');
    heading.textContent = title;

    descriptionBox.appendChild(heading);

    if (descriptionHtml) {
      const content = document.createElement('div');
      content.className = 'speexx-mega-description__content';
      content.innerHTML = descriptionHtml;
      descriptionBox.appendChild(content);
    }
  }

  function bindMegaMenus() {
    document.querySelectorAll('.speexx-mega-sub-menu').forEach((panel) => {
      const links = panel.querySelectorAll('.speexx-mega-products > li > .menu-link');
      if (!links.length) return;

      links.forEach((link) => {
        link.addEventListener('mouseenter', function () {
          setDescription(link);
        });
        link.addEventListener('focus', function () {
          setDescription(link);
        });
      });

      setDescription(links[0]);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindMegaMenus);
  } else {
    bindMegaMenus();
  }
})();
