import dayjs from 'dayjs'
import 'dayjs/locale/en'
import enUS from '@arco-design/web-vue/es/locale/lang/en-us.js'
import { addI18nMessages, useLocale } from '@arco-design/web-vue/es/locale/index.js'

addI18nMessages({ 'en-US': enUS, 'zh-CN': enUS }, { overwrite: true })
useLocale('en-US')

// Arco date-picker maps "en-US" -> dayjs.Ls["en-us"], but dayjs only registers "en".
dayjs.locale('en')
if (dayjs.Ls?.en) {
  dayjs.Ls['en-us'] = dayjs.Ls.en
  dayjs.Ls['zh-cn'] = dayjs.Ls.en
}
dayjs.locale('en')

export default defineNuxtPlugin({
  name: 'arco-locale',
  enforce: 'pre',
})
