Nova.booting((Vue, router, store) => {
  Vue.component('index-gmap', require('./components/IndexField'))
  Vue.component('detail-gmap', require('./components/DetailField'))
  Vue.component('form-gmap', require('./components/FormField'))
})
