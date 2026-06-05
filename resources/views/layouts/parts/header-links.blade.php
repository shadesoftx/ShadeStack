@include('layouts.parts.header-links-start')

@if (user()->hasAppAccess())
    <a class="hide-over-l" href="{{ url('/search') }}">@icon('search'){{ trans('common.search') }}</a>
    @if(userCanOnAny(\BookStack\Permissions\Permission::View, \BookStack\Entities\Models\Bookshelf::class) || userCan(\BookStack\Permissions\Permission::BookshelfViewAll) || userCan(\BookStack\Permissions\Permission::BookshelfViewOwn))
        <a href="{{ url('/shelves/products') }}"
           data-shortcut="shelves_view">@icon('bookshelf'){{ trans('entities.nav_products') }}</a>
        <a href="{{ url('/books/start-here/page/vendor-index') }}"
           data-shortcut="books_view">@icon('chapter'){{ trans('entities.nav_vendors') }}</a>
        <a href="{{ url('/shelves') }}"
           data-shortcut="shelves_view">@icon('bookshelf'){{ trans('entities.shelves') }}</a>
    @endif
    <a href="{{ url('/books') }}" data-shortcut="books_view">@icon('books'){{ trans('entities.nav_guides') }}</a>
    @if(!user()->isGuest() && userCan(\BookStack\Permissions\Permission::SettingsManage))
        <a href="{{ url('/settings') }}"
           data-shortcut="settings_view">@icon('settings'){{ trans('settings.settings') }}</a>
    @endif
    @if(!user()->isGuest() && userCan(\BookStack\Permissions\Permission::UsersManage) && !userCan(\BookStack\Permissions\Permission::SettingsManage))
        <a href="{{ url('/settings/users') }}"
           data-shortcut="settings_view">@icon('users'){{ trans('settings.users') }}</a>
    @endif
@endif

@if(user()->isGuest())
    @if(setting('registration-enabled') && config('auth.method') === 'standard')
        <a href="{{ url('/register') }}">@icon('new-user'){{ trans('auth.sign_up') }}</a>
    @endif
    <a href="{{ url('/login')  }}">@icon('login'){{ trans('auth.log_in') }}</a>
@endif
