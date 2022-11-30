Nova.booting((Vue, router, store) => {
  router.addRoutes([
    {
      name: 'meetings',
      path: '/meetings',
      redirect: '/meetings/1'
    },
    {
      name: 'meetings-page',
      path: '/meetings/:page',
      component: require('./components/Tool'),
    },
  ])
})
