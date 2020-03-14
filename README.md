# Info about models
Extract information about Laravel models for use in code generation,
testing or other development tools.

## Progress
Supported relationship types:
- [x] hasOne
- [x] hasMany
- [x] hasOneThrough
- [x] hasManyThrough
- [x] belongsTo
- [x] belongsToMany
- [ ] morphTo
- [ ] morphOne
- [ ] morphMany
- [ ] morphToMany
- [ ] morphedByMany

Other things:
- [x] Code structure
  - [x] Parse relations in parent classes (extend).
  - [x] Parse relations in traits.
- [ ] Support simple relationship methods that don't have logic.
  - [x] Extract relationship type from return type.
  - [x] Extract relationship type from eloquent helper method (`hasMany()`, etc.).
  - [x] Parse parameters of supported relationship types.
  - [ ] Make primary key and other values dynamic based on class code or other config instead of always using `id` as a default.
  - [x] Ignore comments.

I don't actively try to implement everything on the list. I only work on this when I need it to support something for
use in other projects. Feel free to ask me in an issue if you want to contribute.

## Usage
```php
<?php

use App\Models\User;
use MarkKremer\InfoAboutModels\ModelParser;

$userModelInfo = (new ModelParser)->parseClass(User::class);
var_dump($userModelInfo);
```