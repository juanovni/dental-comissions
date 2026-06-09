<?php

namespace App\Filament\Resources\SocialComments\Pages;

use App\Filament\Resources\SocialComments\SocialCommentResource;
use Filament\Resources\Pages\ListRecords;

class ListSocialComments extends ListRecords
{
    protected static string $resource = SocialCommentResource::class;
}
