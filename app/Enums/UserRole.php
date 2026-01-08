<?php

namespace App\Enums;

enum UserRole : string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Staff = 'staff';
    case Student = 'student';
}
