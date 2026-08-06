<?php
namespace App\Policies;
use App\Models\Form; use App\Models\User; use App\Support\Privileges;
class FormPolicy {
 public function viewAny(User $user): bool { return $user->canAccessModule('forms')||$user->hasPrivilege(Privileges::FORMS_VIEW)||$user->hasPrivilege(Privileges::FORMS_MANAGE); }
 public function view(User $user,Form $form): bool { return $user->tenant_id===$form->tenant_id&&$this->viewAny($user); }
 public function create(User $user): bool { return $user->hasPrivilege(Privileges::FORMS_MANAGE)||$user->isOwnerOrAdmin(); }
 public function update(User $user,Form $form): bool { return $user->tenant_id===$form->tenant_id&&($user->hasPrivilege(Privileges::FORMS_MANAGE)||$user->isOwnerOrAdmin()); }
 public function delete(User $user,Form $form): bool { return $this->update($user,$form); }
}
