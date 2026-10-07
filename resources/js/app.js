import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import registerConfirmationDialog from './confirmation-dialog.js';
import registerPhotographyForm from './fotografia-form.js';
import './data-hora.js';

registerConfirmationDialog(Alpine);
registerPhotographyForm(Alpine);

Livewire.start();
