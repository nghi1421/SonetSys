import { createI18n } from 'vue-i18n'
import admin from './locales/en/admin.json'
import advertising from './locales/en/advertising.json'
import auth from './locales/en/auth.json'
import chat from './locales/en/chat.json'
import common from './locales/en/common.json'
import feed from './locales/en/feed.json'
import follow from './locales/en/follow.json'
import groups from './locales/en/groups.json'
import menu from './locales/en/menu.json'
import notifications from './locales/en/notifications.json'
import stories from './locales/en/stories.json'
import storage from './locales/en/storage.json'
import wallet from './locales/en/wallet.json'

export const SUPPORTED_LOCALES = ['en'] as const
export type SupportedLocale = (typeof SUPPORTED_LOCALES)[number]

export const DEFAULT_LOCALE: SupportedLocale = 'en'

const en = {
  common,
  auth,
  chat,
  feed,
  groups,
  menu,
  storage,
  admin,
  notifications,
  stories,
  follow,
  wallet,
  advertising,
}

export const i18n = createI18n({
  legacy: false,
  locale: DEFAULT_LOCALE,
  fallbackLocale: DEFAULT_LOCALE,
  messages: { en },
})
