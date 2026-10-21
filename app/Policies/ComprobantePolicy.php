<?php

namespace App\Policies;

use App\Models\Comprobante;
use App\Models\User;

class ComprobantePolicy
{
    public function view(User $user, Comprobante $comprobante): bool
    {
        return $user->id === $comprobante->user_id;
    }

    public function update(User $user, Comprobante $comprobante): bool
    {
        return $user->id === $comprobante->user_id;
    }

    public function delete(User $user, Comprobante $comprobante): bool
    {
        return $user->id === $comprobante->user_id;
    }
}
