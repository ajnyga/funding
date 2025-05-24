<template>
	<PkpGridWrapper
      :key="`${submissionId}`"
      grid-component="plugins.generic.funding.controllers.grid.FunderGridHandler"
      :params="params"
	></PkpGridWrapper>
</template>
<script setup>
import {computed} from 'vue';

const props = defineProps({
	submissionId: {type: String, required: true},
});

const params = computed(() => ({
	submissionId: props.submissionId,
}));
</script>