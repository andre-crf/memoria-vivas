import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import registerConfirmationDialog from './confirmation-dialog.js';
import registerPhotographyForm from './fotografia-form.js';
import registerPublicPhotoViewer from './public-photo-viewer.js';
import registerContributionForm from './contribution-form.js';
import registerToaster from './toaster.js';
import registerDrawer from './drawer.js';
import registerHeroCarousel from './hero-carousel.js';
import './data-hora.js';

registerConfirmationDialog(Alpine);
registerPhotographyForm(Alpine);
registerPublicPhotoViewer(Alpine);
registerContributionForm(Alpine);
registerToaster(Alpine);
registerDrawer(Alpine);
registerHeroCarousel(Alpine);

Livewire.start();
