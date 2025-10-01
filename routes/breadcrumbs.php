<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Home
Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Inicio', url('/'));
});
// perfil
Breadcrumbs::for('perfil', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Perfil', route('perfil'));
});

// login
Breadcrumbs::for('login', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Login', route('login'));
});

// usuarios
Breadcrumbs::for('usuarios.index', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Usuarios', route('usuarios.index'));
});
Breadcrumbs::for('usuarios.create', function (BreadcrumbTrail $trail) {
    $trail->parent('usuarios.index');
    $trail->push('Nuevo', route('usuarios.create'));
});
Breadcrumbs::for('usuarios.edit', function (BreadcrumbTrail $trail,$user) {
    $trail->parent('usuarios.index');
    $trail->push('Editar', route('usuarios.edit',$user->id));
});

// comunidad
Breadcrumbs::for('comunidad.index', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Comunidad', route('comunidad.index'));
});
Breadcrumbs::for('comunidad.create', function (BreadcrumbTrail $trail) {
    $trail->parent('comunidad.index');
    $trail->push('Nuevo', route('comunidad.create'));
});
Breadcrumbs::for('comunidad.edit', function (BreadcrumbTrail $trail,$user) {
    $trail->parent('comunidad.index');
    $trail->push('Editar', route('comunidad.edit',$user->id));
});

// niños
Breadcrumbs::for('ninios.index', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Niños', route('ninios.index'));
});
Breadcrumbs::for('ninios.create', function (BreadcrumbTrail $trail) {
    $trail->parent('ninios.index');
    $trail->push('Nuevo', route('ninios.create'));
});
Breadcrumbs::for('ninios.importar', function (BreadcrumbTrail $trail) {
    $trail->parent('ninios.index');
    $trail->push('Importar', route('ninios.importar'));
});
Breadcrumbs::for('ninios.edit', function (BreadcrumbTrail $trail,$user) {
    $trail->parent('ninios.index');
    $trail->push('Editar', route('ninios.edit',$user->id));
});

// mis niños
Breadcrumbs::for('mis-ninios.index', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Mis niños', route('mis-ninios.index'));
});
Breadcrumbs::for('mis-ninios.create', function (BreadcrumbTrail $trail) {
    $trail->parent('mis-ninios.index');
    $trail->push('Nuevo', route('mis-ninios.create'));
});
Breadcrumbs::for('mis-ninios.edit', function (BreadcrumbTrail $trail,$user) {
    $trail->parent('mis-ninios.index');
    $trail->push('Editar', route('mis-ninios.edit',$user->id));
});

// cartas
Breadcrumbs::for('cartas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Cartas', route('cartas.index'));
});
Breadcrumbs::for('cartas.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cartas.index');
    $trail->push('Nuevo', route('cartas.create'));
});
Breadcrumbs::for('cartas.show', function (BreadcrumbTrail $trail,$user) {
    $trail->parent('cartas.index');
    $trail->push('Ver', route('cartas.show',$user->id));
});
Breadcrumbs::for('cartas.edit', function (BreadcrumbTrail $trail,$user) {
    $trail->parent('cartas.show',$user);
    $trail->push('Ver', route('cartas.edit',$user->id));
});
Breadcrumbs::for('cartas.documentos', function (BreadcrumbTrail $trail,$user) {
    $trail->parent('cartas.index');
    $trail->push('Documentos', route('cartas.documentos',$user->id));
});
