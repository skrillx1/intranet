(function (Drupal) {
  Drupal.behaviors.cssGridLayout = {
    attach: function attach(context) {
      once('cssGridLayout', '.css-grid-layout-settings select', context).forEach(el => {
        el.addEventListener('change', (elm) => {
          const input = elm.currentTarget.parentElement.previousElementSibling.children.item(1);
          switch (elm.target.value) {
            case 'minmax':
              input.type = 'text';
              input.value = '200px,400px';
              break
            case 'auto':
            case 'min-content':
            case 'max-content':
              input.value = '';
              input.readOnly = true;
              break;
            default:
              input.type = 'number';
              input.value = '1';
              break;
          }
        });
      });
    }
  };
})(Drupal);
