Statamic.booting(()=>{Statamic.$conditions.add("isOnSite",({target:e,params:t})=>{const i=Statamic.$config.get("selectedSite");return t.includes(i)})});
