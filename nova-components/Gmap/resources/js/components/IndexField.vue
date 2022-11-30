<template>
    <span>{{ pos.lat }} : {{ pos.lng }}</span>
</template>

<script>
export default {
    props: ['resourceName', 'field'],
    data() {
        return {
            pos:{lat: 0, lng: 0},
        };
    },
    mounted(){
        this.pos = JSON.parse(this.field.value);
    },
}
</script>
