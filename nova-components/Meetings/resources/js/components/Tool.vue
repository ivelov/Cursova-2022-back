<template>
    <div>
        <heading class="mb-6">Meetings</heading>

        <card
            class="flex flex-col meetings-div"
            :class="pageInfo.meetings.length > 0 ? '' : 'justify-center'"
        >
        <div
        v-if="loading"
        :style="{
            width: `50%`,
            height: '3px',
            opacity: 1,
            'background-color': 'var(--primary)',
        }"
        class="progress"
        />
        <table
        v-else-if="pageInfo.meetings? Object.keys(pageInfo.meetings).length != 0 : false"
            class="table w-full"
            cellpadding="0"
            cellspacing="0"
        >
        <thead>
          <tr>
            <th>uuid</th>
            <th>id</th>
            <th>host_id</th>
            <th>topic/report_title</th>
            <th>type</th>
            <th>start_time</th>
            <th>timezone</th>
            <th>created_at</th>
            <th>join_url</th>
          </tr>
        </thead>
        <tbody> 
          <tr
            class="meeting-row"
            v-for="(meeting, index) in pageInfo.meetings"
            :key="meeting.id"
          >
            <td>{{meeting.uuid}}</td>
            <td>{{meeting.id}}</td>
            <td>{{meeting.host_id}}</td>
            <td>{{meeting.topic}}</td>
            <td>{{meeting.type}}</td>
            <td>{{meeting.start_time}}</td>
            <td>{{meeting.timezone}}</td>
            <td>{{meeting.created_at}}</td>
            <td>{{meeting.join_url}}</td>
          </tr> 
        </tbody>
        </table> 
         <div v-else class="text-center flex justify-center">
            No results
        </div> 
        </card> 
         <div class="bg-20 rounded-b-lg">
            <nav class="flex nav-btns">
                <button
                :disabled="!hasPreviousPages || loading"
                class="font-mono btn btn-link h-9 min-w-9 px-2 border-r border-50"
                :class="{
                    'text-primary dim': hasPreviousPages,
                    'text-80 opacity-50': !hasPreviousPages || loading,
                }"
                rel="prev"
                @click.prevent="prevPage()"
                dusk="previous"
                >
                &lsaquo;
                </button>

                <div class="flex items-center">
                    Page {{page}} of {{pageInfo.maxPage}}
                </div>

                <button
                :disabled="!hasMorePages || loading"
                class="font-mono btn btn-link h-9 min-w-9 px-2 border-r border-50"
                :class="{
                    'text-primary dim': hasMorePages,
                    'text-80 opacity-50': !hasMorePages || loading,
                }"
                rel="next"
                @click.prevent="nextPage()"
                dusk="next"
                >
                &rsaquo;
                </button>

            <slot />
            </nav>
        </div> 
    </div>
</template>

<script>
export default {
    metaInfo() {
        return {
          title: 'Meetings',
        }
    },
    data: () => ({
        pageInfo: {
            meetings:{},
            maxPage: 1
          },
          loading: false,
          page: 1
    }),
    computed:{
        hasMorePages(){
            return this.page < this.pageInfo.maxPage; 
        },
        hasPreviousPages(){
            return this.page > 1; 
        },
    },
    mounted() {
        this.loading = true;
        this.page =  this.$route.params.page;
        axios.get("http://127.0.0.1:8000/meetings/"+this.page).then((response) => {
            this.pageInfo = response.data;
        }).finally(() => {
            this.loading = false;
        });
    },
    methods:{
        nextPage() {
            this.page = parseInt(this.page) + 1;
            this.loading = true;
            axios.get("http://127.0.0.1:8000/meetings/"+this.page).then((response) => {
                this.pageInfo = response.data;
            }).finally(() => {
                this.loading = false;
            });
            this.$router.push("/meetings/" + this.page);
        },
        prevPage() {
            this.page = parseInt(this.page) - 1;
            this.loading = true;
            axios.get("http://127.0.0.1:8000/meetings/"+this.page).then((response) => {
                this.pageInfo = response.data;
            }).finally(() => {
                this.loading = false;
            });
            this.$router.push("/meetings/" + this.page);
        },
    }
}
</script>

<style>

.nav-btns{
    justify-content: space-between;
}

.meetings-div{
    min-height: 50px;
    max-width: 100%;
    overflow-x: auto;
}

.progress {
  position: fixed;
  top: 0px;
  left: 0px;
  right: 0px;
  height: 2px;
  width: 0%;
  transition: width 0.2s, opacity 0.4s;
  opacity: 1;
  background-color: #efc14e;
  z-index: 999999;
}
</style>
