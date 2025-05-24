import FundingGrid from "./Components/FundingGrid.vue";

pkp.registry.registerComponent("FundingGrid", FundingGrid);

pkp.registry.storeExtend("workflow", (piniaContext) => {
  const workflowStore = piniaContext.store;

  workflowStore.extender.extendFn("getMenuItems", (menuItems) => {

    const { useLocalize } = pkp.modules.useLocalize;
    const { t } = useLocalize();

    return menuItems.map((item) => {
      if (item.key === "publication") {
        return {
          ...item,
          items: [
            ...item.items,
            {
              key: "publication_funding",
              label: t('plugins.generic.funding.fundingData'),
              state: {
                primaryMenuItem: "publication",
                secondaryMenuItem: "funding",
                title: t('plugins.generic.funding.publication.fundingData'),
              },
            },
          ],
        };
      }
      return item;
    });
  });

  workflowStore.extender.extendFn("getPrimaryItems", (primaryItems, args) => {
    if (
      args?.selectedMenuState?.primaryMenuItem === "publication" &&
      args?.selectedMenuState?.secondaryMenuItem === "funding"
    ) {
      return [
        {
          component: "FundingGrid",
          props: {
            submissionId: args?.submission?.id?.toString?.() ?? "",
          },
        },
      ];
    }

    return primaryItems;
  });
});