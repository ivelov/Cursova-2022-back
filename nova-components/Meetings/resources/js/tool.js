Nova.booting((Vue, router, store) => {
  router.addRoutes([
    {
      name: 'meetings',
      path: '/meetings',
      component: require('./components/Tool'),
    },
  ])
})
