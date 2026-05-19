<?php

declare(strict_types=1);

namespace App\Actions\Screening;

use App\Enums\DocumentVerificationStatus;
use App\Models\ApplicationDocument;
use App\Models\User;

class VerifyApplicationDocumentAction
{
    public function handle(
        ApplicationDocument $document,
        User $verifier,
        DocumentVerificationStatus $status,
        ?string $remark = null,
    ): ApplicationDocument {
        $document->update([
            'verification_status' => $status,
            'verification_remark' => $remark,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);

        return $document->refresh();
    }
}
