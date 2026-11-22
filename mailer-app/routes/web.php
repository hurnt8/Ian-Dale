<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Propet;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Authentification ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// --- Désinscription (lien signé présent dans chaque email) ---
Route::middleware('signed')->group(function () {
    Route::get('/desinscription', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
    Route::post('/desinscription', [UnsubscribeController::class, 'store'])->name('unsubscribe.store');
});

// --- Accueil : redirection selon le rôle ---
Route::get('/', function (Request $request) {
    return $request->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('propet.campaigns.index');
})->middleware('auth')->name('home');

// --- Formulaire de réponse ouvert depuis l'email (lien personnel à jeton, sans connexion) ---
Route::get('/avis/{token}', [FormController::class, 'show'])->where('token', '[A-Za-z0-9]{48}')->name('form.show');
Route::post('/avis/{token}', [FormController::class, 'store'])->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:10,1')->name('form.store');

// --- Vues : pages publiques autonomes (anonymes, adresse en clair /vue/{slug}) ---
Route::get('/vue/{slug}', [PublicPageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('page.show');
Route::post('/vue/{slug}', [PublicPageController::class, 'store'])->where('slug', '[a-z0-9-]+')->middleware('throttle:10,1')->name('page.store');

// --- Communs aux deux rôles : vérification d'adresses + aperçu des emails ---
Route::middleware('auth')->group(function () {
    Route::get('/verification', [VerificationController::class, 'show'])->name('verification.show');
    Route::post('/verification', [VerificationController::class, 'check'])->middleware('throttle:20,1')->name('verification.check');
    Route::get('/apercu/message/{template}', [PreviewController::class, 'template'])->name('preview.template');
    Route::get('/apercu/liste/{campaign}', [PreviewController::class, 'campaign'])->name('preview.campaign');
    Route::get('/apercu/formulaire/{template}', [PreviewController::class, 'form'])->name('preview.form');
    Route::get('/apercu/vue/{page}', [PreviewController::class, 'page'])->name('preview.page'); // admin uniquement (contrôlé dans le contrôleur)
});

// --- Espace PROPET ---
Route::middleware(['auth', 'role:propet'])->prefix('propet')->name('propet.')->group(function () {
    Route::get('/campagnes', [Propet\CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campagnes/nouvelle', [Propet\CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campagnes', [Propet\CampaignController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('campaigns.store');
    Route::get('/campagnes/{campaign}', [Propet\CampaignController::class, 'show'])->name('campaigns.show');
    Route::get('/emails', [RecipientController::class, 'index'])->name('emails.index');
    Route::get('/reponses', [ResponseController::class, 'index'])->name('responses.index');
    Route::get('/reponses/export', [ResponseController::class, 'export'])->name('responses.export');
});

// --- Espace ADMIN ---
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/emails', [RecipientController::class, 'index'])->name('emails.index');
    Route::get('/emails/export', [RecipientController::class, 'export'])->name('emails.export');
    Route::get('/reponses', [ResponseController::class, 'index'])->name('responses.index');
    Route::get('/reponses/export', [ResponseController::class, 'export'])->name('responses.export');

    Route::get('/campagnes', [Admin\CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campagnes/{campaign}', [Admin\CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/campagnes/{campaign}/valider', [Admin\CampaignController::class, 'approve'])->name('campaigns.approve');
    Route::post('/campagnes/{campaign}/refuser', [Admin\CampaignController::class, 'reject'])->name('campaigns.reject');
    Route::post('/campagnes/{campaign}/relancer', [Admin\CampaignController::class, 'retry'])->name('campaigns.retry');
    Route::delete('/campagnes/{campaign}', [Admin\CampaignController::class, 'destroy'])->name('campaigns.destroy');

    Route::resource('messages', Admin\TemplateController::class)
        ->except('show')
        ->parameters(['messages' => 'template'])
        ->names('templates');

    // Vues publiques autonomes
    Route::get('/vues/{page}/reponses', [Admin\PageController::class, 'responses'])->name('pages.responses');
    Route::get('/vues/{page}/reponses/export', [Admin\PageController::class, 'exportResponses'])->name('pages.responses.export');
    Route::resource('vues', Admin\PageController::class)
        ->except('show')
        ->parameters(['vues' => 'page'])
        ->names('pages');

    Route::resource('utilisateurs', Admin\UserController::class)
        ->except('show')
        ->parameters(['utilisateurs' => 'user'])
        ->names('users');

    Route::get('/parametres', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/parametres', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::delete('/suppressions/{unsubscribe}', [Admin\SettingController::class, 'removeSuppression'])->name('suppressions.destroy');

    Route::get('/presentation', [Admin\PresentationController::class, 'edit'])->name('presentation.edit');
    Route::put('/presentation', [Admin\PresentationController::class, 'update'])->name('presentation.update');
});
