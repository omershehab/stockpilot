import './bootstrap';

import Alpine from 'alpinejs';
import labelDesigner from './label-designer';

window.Alpine = Alpine;

Alpine.data('labelDesigner', labelDesigner);

Alpine.start();
