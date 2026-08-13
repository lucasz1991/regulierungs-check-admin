<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        // Jetstream registriert die Livewire-Komponente auch dann, wenn das
        // sichtbare Account-Deletion-Feature deaktiviert ist. Dieser zweite,
        // serverseitige Schutz verhindert daher auch direkte bzw. alte
        // Livewire-Snapshots, die sonst audit-referenzierte Benutzer hart
        // loeschen koennten.
        abort(403, 'Die Kontoloeschung ist im Adminbereich deaktiviert.');
    }
}
